<?php

class Panegyric_DB_Migrator
{
    const db_version = '1.0';

    public function __construct()
    {
        global $wpdb;
        $this->prefix = $wpdb->prefix . 'panegyric';
        $this->org_table = $this->prefix . "_org";
        $this->user_table = $this->prefix . "_users";
        $this->repo_table = $this->prefix . "_repo";
        $this->pr_table = $this->prefix . "_prs";
    }

    public function table_exists($name)
    {
        global $wpdb;
        $rows = $wpdb->query("SHOW TABLES LIKE '$name';");
        if ($rows == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function create_table($name, $sql)
    {
        if (!$this->table_exists($name)) {
            global $wpdb;
            $charset_collate = $wpdb->get_charset_collate();
            $wpdb->query("CREATE TABLE $name ($sql) $charset_collate;");
        }
    }

    public function table_install()
    {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $this->create_table($this->org_table, "
                org VARCHAR(39),
                status ENUM('success', 'not-found', 'denied', 'not-checked'),
                updated TIMESTAMP NULL,
                PRIMARY KEY  (org)");
        $this->create_table($this->user_table, "
                username VARCHAR(39),
                name TEXT NULL,
                org VARCHAR(39) NULL,
                status ENUM('success', 'not-found', 'denied', 'not-checked'),
                updated TIMESTAMP NULL,
                prs_updated TIMESTAMP NULL,
                PRIMARY KEY  (username),
                FOREIGN KEY  (org) REFERENCES {$this->org_table}(org) ON DELETE CASCADE");
        $this->create_table($this->repo_table, "
                id INT NOT NULL AUTO_INCREMENT,
                name TEXT,
                description TEXT,
                url TEXT,
                html_url TEXT,
                owner VARCHAR(39),
                updated TIMESTAMP NULL,
                PRIMARY KEY  (id)");
        $this->create_table($this->pr_table, "
                id INT NOT NULL AUTO_INCREMENT,
                url TEXT,
                user VARCHAR(39) NOT NULL,
                repo INT NOT NULL,
                title TEXT NOT NULL,
                updated_at TIMESTAMP NULL,
                PRIMARY KEY  (id),
                FOREIGN KEY  (user) REFERENCES {$this->prefix}_users(username) ON DELETE CASCADE,
                FOREIGN KEY  (repo) REFERENCES {$this->prefix}_repo(id) ON DELETE CASCADE");

        add_option('panegyric_db_version', $this::db_version);
    }

    public function create_org($name)
    {
        global $wpdb;
        if ($wpdb->query($wpdb->prepare("select org from {$this->org_table} where org = %s", $name)) == 0) {
            $wpdb->insert(
                $this->org_table,
                array(
                    'org' => $name,
                    'status' => 'not-checked',
                )
            );
        }
    }

    public function create_user($name, $org = null)
    {
        global $wpdb;
        $values = array(
            'username' => $name,
            'status' => 'not-checked',
        );
        if (!is_null($org)) {
            $values['org'] = $org;
        }
        if ($wpdb->query($wpdb->prepare("select username from {$this->user_table} where username = %s", $name)) == 0) {
            $wpdb->insert(
                $this->user_table,
                $values
            );
        }
    }

    public function get_prs($orgs, $users, $limit)
    {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT username from {$this->user_table} WHERE FIND_IN_SET(org, %s)", implode(",", $orgs));
        $extra_users = $wpdb->get_col($sql);
        $users = array_merge($users, $extra_users);

        $sql = $wpdb->prepare("SELECT *, r.html_url as repo_url, pr.url as pr_url, r.name as repo_name, u.username as username
                FROM {$this->pr_table} pr
                JOIN {$this->repo_table} r on pr.repo = r.id
                JOIN {$this->user_table} u on pr.user = u.username
                WHERE FIND_IN_SET(u.username, %s) AND
                (NOT FIND_IN_SET(r.owner, %s))
                ORDER BY pr.updated_at DESC
                LIMIT %d", implode(",", $users), implode(",", array_merge($users, $orgs)), $limit);
        return $wpdb->get_results($sql);
    }

    public function no_updates($orgs, $users)
    {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT username from {$this->user_table} WHERE prs_updated IS NULL AND
                (FIND_IN_SET(username, %s) OR
                FIND_IN_SET(org, %s))
                UNION
                SELECT org from {$this->org_table} WHERE updated IS NULL AND
                FIND_IN_SET(org, %s)", implode(",", $users), implode(",", $orgs), implode(",", $orgs));
        $missing_users = $wpdb->get_col($sql);
        return $missing_users;
    }

    public function update_org($org, $users)
    {
        global $wpdb;
        foreach ($users as $user) {
            $this->create_user($user, $org);
        }
        $sql = $wpdb->prepare("SELECT username from {$this->user_table} where org=%s;", $org);
        $all_users = $wpdb->get_col($sql);
        foreach ($all_users as $user) {
            if (!in_array($user, $users)) {
                $this->delete_user($user);
            }
        }
        $wpdb->query($wpdb->prepare("update {$this->org_table} set updated=NOW(), status='success' where org=%s;", $org));
    }

    public function org_missing($org)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("update {$this->org_table} set updated=NOW(), status='not-found' where org=%s;", $org));
    }

    public function set_user_name($username, $name)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("update {$this->user_table} set updated=NOW(), status='success', name=%s where username=%s;", $name, $username));
    }

    public function user_missing($username)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("update {$this->user_table} set updated=NOW(), status='not-found' where username=%s;", $username));
    }

    public function user_denied($username)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("update {$this->user_table} set updated=NOW(), status='denied' where username=%s;", $username));
    }

    public function delete_user($username)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("delete from {$this->user_table} WHERE username=%s;", $username));
    }

    public function delete_org($name)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("delete from {$this->org_table} WHERE org=%s;", $name));
    }

    public function get_repo_by_url($url)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("select * from {$this->repo_table} where url = %s", $url));
    }

    public function add_repo($repo)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("insert into {$this->repo_table} (name, description, url, html_url, owner, updated) values(%s,%s,%s,%s,%s,NOW());", $repo->name, $repo->description, $repo->url, $repo->html_url, $repo->owner->login));
    }

    public function get_pr_by_url($url)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("select * from {$this->pr_table} where url = %s", $url));
    }

    public function add_pr($pr, $repo, $username)
    {
        global $wpdb;
        $updated_at = DateTime::createFromFormat(DateTime::ATOM, $pr->updated_at);
        $wpdb->query($wpdb->prepare("insert into {$this->pr_table} (url, user, repo, title, updated_at) values(%s, %s, %d, %s, FROM_UNIXTIME(%d))", $pr->html_url, $username, $repo->id, $pr->title, $updated_at->getTimestamp()));
    }

    public function prs_updated($username)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("update {$this->user_table} set prs_updated=NOW() where username=%s;", $username));
    }
}

function panegyric_table_install()
{
    $db = new Panegyric_DB_Migrator();
    $db->table_install();
}

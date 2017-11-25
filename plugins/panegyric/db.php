<?php

class DB_Migrator
{
    public static $db_version = '1.0';

    public function __construct()
    {
        global $wpdb;
        $this->prefix = $wpdb->prefix . 'panegyric';
        $this->tag_table = $this->prefix . "_tag_names";
        $this->org_table = $this->prefix . "_org";
        $this->tag_org_table = $this->prefix . "_org_tag";
        $this->tag_user_table = $this->prefix . "_user_tag";
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

        $this->create_table($this->tag_table, "
                name VARCHAR(255),
                PRIMARY KEY  (name)");
        $this->create_table($this->org_table, "
                org VARCHAR(39),
                status ENUM('success', 'not-found', 'denied', 'not-checked'),
                updated TIMESTAMP NULL,
                PRIMARY KEY  (org)");
        $this->create_table($this->prefix . "_users", "
                username VARCHAR(39),
                org VARCHAR(39) NULL,
                status ENUM('success', 'not-found', 'denied', 'not-checked'),
                updated TIMESTAMP NULL,
                prs_updated TIMESTAMP NULL,
                PRIMARY KEY  (username),
                FOREIGN KEY  (org) REFERENCES {$this->org_table}(org) ON DELETE CASCADE");
        $this->create_table($this->tag_org_table, "
                tag VARCHAR(255) NOT NULL,
                org VARCHAR(39) NOT NULL,
                PRIMARY KEY  (tag, org),
                FOREIGN KEY  (tag) REFERENCES {$this->tag_table}(name) ON DELETE CASCADE,
                FOREIGN KEY  (org) REFERENCES {$this->org_table}(org) ON DELETE CASCADE");
        $this->create_table($this->tag_user_table, "
                tag VARCHAR(255) NOT NULL,
                username VARCHAR(39) NOT NULL,
                PRIMARY KEY  (tag, username),
                FOREIGN KEY  (tag) REFERENCES {$this->tag_table}(name) ON DELETE CASCADE,
                FOREIGN KEY  (username) REFERENCES {$this->prefix}_users(username) ON DELETE CASCADE");
        $this->create_table($this->prefix . "_repo", "
                id INT NOT NULL AUTO_INCREMENT,
                name TEXT,
                description TEXT,
                url TEXT,
                updated TIMESTAMP NULL,
                PRIMARY KEY  (id)");
        $this->create_table($this->prefix . "_prs", "
                id INT NOT NULL AUTO_INCREMENT,
                url TEXT,
                user VARCHAR(39) NOT NULL,
                repo INT NOT NULL,
                title TEXT NOT NULL,
                PRIMARY KEY  (id),
                FOREIGN KEY  (user) REFERENCES {$this->prefix}_users(username) ON DELETE CASCADE,
                FOREIGN KEY  (repo) REFERENCES {$this->prefix}_repo(id) ON DELETE CASCADE");

        add_option('panegyric_db_version', $this->db_version);
    }

    public function create_org($name)
    {
        global $wpdb;
        if ($wpdb->query("select org from {$this->org_table} where org = '$name'") == 0) {
            $wpdb->insert(
                $this->org_table,
                array(
                    'org' => $name,
                    'status' => 'not-checked',
                )
            );
        }
    }

    public function create_tag($name)
    {
        global $wpdb;
        $this->create_org($name);
        if ($wpdb->query("select name from {$this->tag_table} where name = '$name'") == 0) {
            $wpdb->insert(
                $this->tag_table,
                array(
                    'name' => $name,
                )
            );
            $wpdb->insert(
                $this->tag_org_table,
                array(
                    'tag' => $name,
                    'org' => $name
                )
            );
        }
    }
}

function panegyric_table_install()
{
    $db = new DB_Migrator();
    $db->table_install();
}

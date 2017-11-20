<?php
global $panegyric_db_version;
$panegyric_db_version = '1.0';

global $wpdb;
$prefix = $wpdb->prefix . 'panegyric';
$tag_table = $prefix . "_tag_names";
$org_table = $prefix . "_org";

function table_exists($name)
{
    global $wpdb;
    $rows = $wpdb->query("SHOW TABLES LIKE '$name';");
    if ($rows == 1) {
        return true;
    } else {
        return false;
    }
}

function create_table($name, $sql)
{
    if (!table_exists($name)) {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $wpdb->query("CREATE TABLE $name ($sql) $charset_collate;");
    }
}

function panegyric_table_install()
{
    global $wpdb;
    global $panegyric_db_version;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    create_table($tag_table, "
        name VARCHAR(255),
        org_list TEXT NOT NULL,
        user_list TEXT NOT NULL,
        exclude_user_list TEXT NOT NULL,
        PRIMARY KEY  (name)");
    create_table($org_table, "
        org VARCHAR(39),
        status ENUM('success', 'not-found', 'denied', 'not-checked'),
        updated TIMESTAMP NULL,
        PRIMARY KEY  (org)");
    create_table($prefix . "_users", "
        username VARCHAR(39),
        org VARCHAR(39) NULL,
        status ENUM('success', 'not-found', 'denied', 'not-checked'),
        updated TIMESTAMP NULL,
        prs_updated TIMESTAMP NULL,
        PRIMARY KEY  (username),
        FOREIGN KEY  (org) REFERENCES {$prefix}_org(org) ON DELETE CASCADE");
    create_table($prefix . "_repo", "
        id INT NOT NULL AUTO_INCREMENT,
        name TEXT,
        description TEXT,
        url TEXT,
        updated TIMESTAMP NULL,
        PRIMARY KEY  (id)");
    create_table($prefix . "_prs", "
        id INT NOT NULL AUTO_INCREMENT,
        url TEXT,
        user VARCHAR(39) NOT NULL,
        repo INT NOT NULL,
        title TEXT NOT NULL,
        PRIMARY KEY  (id),
        FOREIGN KEY  (user) REFERENCES {$prefix}_users(username) ON DELETE CASCADE,
        FOREIGN KEY  (repo) REFERENCES {$prefix}_repo(id) ON DELETE CASCADE");

    add_option('panegyric_db_version', $panegyric_db_version);
}

function panegyric_create_org($name)
{
    global $wpdb;
    global $org_table;
    if ($wpdb->query("select org from $org_table where org = '$name'") == 0) {
        $wpdb->insert(
            $org_table,
            array(
                'org' => $name,
                'status' => 'not-checked',
            )
        );
    }
}

function panegyric_create_tag($name)
{
    global $wpdb;
    global $tag_table;
    if ($wpdb->query("select name from $tag_table where name = '$name'") == 0) {
        $wpdb->insert(
            $tag_table,
            array(
                'name' => $name,
                'org_list' => $name,
                'user_list' => '',
                'exclude_user_list' => '',
            )
        );
    }
    panegyric_create_org($name);
}

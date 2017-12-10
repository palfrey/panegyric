<?php
/**
 * @package Panegyric
 * @version 0.1
 */
/*
Plugin Name: Panegyric
Plugin URI: http://wordpress.org/plugins/panegyric/
Description: Foo
Author: Tom Parker
Version: 0.1
Author URI: https://tevp.net
*/

function comma_split($instr)
{
    // using strlen trick from http://php.net/manual/en/function.explode.php#111650
    return array_filter(explode(",", $instr), 'strlen');
}

function panegyric_shortcodes_init()
{
    function github_prs_func($atts)
    {
        $db = new DB_Migrator();
        if (array_key_exists("orgs", $atts)) {
            $orgs = comma_split($atts["orgs"]);
            foreach ($orgs as $org) {
                $db->create_org($org);
            }
        } else {
            $orgs = array();
        }
        if (array_key_exists("users", $atts)) {
            $users = comma_split($atts["users"]);
            foreach ($users as $user) {
                $db->create_user($user);
            }
        } else {
            $users = array();
        }
        if (array_key_exists("limit", $atts)) {
            $limit = $atts["limit"];
        } else {
            $limit = 0;
        }
        $prs = $db->get_prs($orgs, $users);
        $outstr = 'Total merged pull requests: '. count($prs) . '<table border="1">';
        foreach ($prs as $pr) {
            $outstr .= "<tr><td>${pr}</td></tr>";
        }
        return $outstr . "</table>";
    }
    add_shortcode('github_prs', 'github_prs_func');
}
add_action('init', 'panegyric_shortcodes_init');
add_action('admin_init', 'setup_ajax');

define('PLUGIN_PATH', plugin_dir_path(__FILE__));
include(PLUGIN_PATH . 'db.php');
include(PLUGIN_PATH . 'admin.php');

register_activation_hook(__FILE__, 'panegyric_table_install');
//register_uninstall hook(__FILE__,'wptuts_uninstall_plugin');

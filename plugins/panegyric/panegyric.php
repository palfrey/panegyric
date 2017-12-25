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

ini_set('display_errors', 'On');
error_reporting(E_ALL);

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
            $limit = 10;
        }
        $prs = $db->get_prs($orgs, $users, $limit);
        $outstr = '<ul class="pangegyric-list">';
        foreach ($prs as $pr) {
            $when = DateTime::createFromFormat('Y-m-d H:i:s', $pr->when);
            $outstr .= "<li class=\"panegyric-item\">
                {$when->format('Y-m-d')}: \"<a href=\"{$pr->pr_url}\">{$pr->title}</a>\"
                was done by <a href=\"https://github.com/{$pr->username}\">{$pr->name}</a>
                for <a href=\"{$pr->repo_url}\">{$pr->repo_name}</a></li>";
        }
        return $outstr . "</ul>";
    }
    add_shortcode('github_prs', 'github_prs_func');
}
add_action('init', 'panegyric_shortcodes_init');
add_action('admin_init', 'setup_ajax');
add_action('panegyric_update', 'panegyric_update');

define('PLUGIN_PATH', plugin_dir_path(__FILE__));
include(PLUGIN_PATH . 'db.php');
include(PLUGIN_PATH . 'admin.php');
include(PLUGIN_PATH . 'admin/cron.php');

function panegyric_activate()
{
    panegyric_table_install();
    if (! wp_next_scheduled('panegyric_update')) {
        wp_schedule_event(time(), 'daily', 'panegyric_update');
    }
}

function panegyric_deactivate()
{
    $timestamp = wp_next_scheduled('panegyric_update');
    wp_unschedule_event($timestamp, 'panegyric_update');
}

register_activation_hook(__FILE__, 'panegyric_activate');
register_deactivation_hook(__FILE__, 'panegyric_deactivate');

//register_uninstall hook(__FILE__,'wptuts_uninstall_plugin');

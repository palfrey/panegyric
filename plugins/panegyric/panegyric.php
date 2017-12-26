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

// based off https://bugs.php.net/bug.php?id=43901
function expand_vars($str, $locals)
{
    while (true) {
        $res=preg_replace_callback(
            '#\{\$([A-Za-z_][A-Za-z0-9_]*)\}#',
            function ($matches) use ($locals) {
                if (!isset($locals[$matches[1]])) {
                    die("Variable ".@$matches[1]." is undefined.");
                }
                return $locals[$matches[1]];
            },
            $str
        );
        if ($res == $str) {
            return $str;
        }
        $str = $res;
    }
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
        if (array_key_exists("format", $atts)) {
            $format = $atts["format"];
        } else {
            $format = '{$updated_at}:
                "<a href="{$pr_url}">{$pr_title}</a>" was done by
                <a href="{$user_url}">{$name}</a> for <a href="{$repo_url}">{$repo_name}</a>';
        }

        $prs = $db->get_prs($orgs, $users, $limit);
        $outstr = '<ul class="pangegyric-list">';
        foreach ($prs as $pr) {
            $outstr .= expand_vars(
                "<li class=\"panegyric-item\">".$format."</li>",
                array(
                    "name" => $pr->name,
                    "pr_title" => $pr->title,
                    "pr_url" => $pr->pr_url,
                    "repo_name" => $pr->repo_name,
                    "repo_url" => $pr->repo_url,
                    "updated_at" => DateTime::createFromFormat('Y-m-d H:i:s', $pr->updated_at)->format('Y-m-d'),
                    "user_url" => "https://github.com/{$pr->username}"
                )
            );
        }
        return $outstr . "</ul>";
    }
    add_shortcode('github_prs', 'github_prs_func');
}

// This is needed because the shortcode code updates the orgs/users/pr table
// With this function hooked in, those get updated on save, not view
function panegyric_run_shortcode_publish($ID)
{
    $post = get_post($ID);
    $content = $post->post_content;
    if (has_shortcode($content, 'github_prs')) {
        do_shortcode($content);
    }
}

add_action('init', 'panegyric_shortcodes_init');
add_action('admin_init', 'panegyric_setup_ajax');
add_action('panegyric_update', 'panegyric_update');
add_action('save_post', 'panegyric_run_shortcode_publish');

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

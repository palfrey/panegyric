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

function panegyric_shortcodes_init()
{
    function github_prs_func($atts)
    {
        $org = $atts['org'];
        panegyric_create_tag($org);
        return "org = $org";
    }
    add_shortcode('github_prs', 'github_prs_func');
}
add_action('init', 'panegyric_shortcodes_init');

define('PLUGIN_PATH', plugin_dir_path(__FILE__));
include(PLUGIN_PATH . 'db.php');
include(PLUGIN_PATH . 'admin.php');

register_activation_hook(__FILE__, 'panegyric_table_install');
//register_uninstall hook(__FILE__,'wptuts_uninstall_plugin');

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

function github_prs_func( $atts ) {
	return "org = {$atts['org']}";
}
add_shortcode( 'github_prs', 'github_prs_func' );

define( 'PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
include( PLUGIN_PATH . 'db.php');
register_activation_hook( __FILE__, 'panegyric_table_install' );
?>

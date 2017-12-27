<?php

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

include_once(PANEGYRIC_PLUGIN_PATH . 'admin/info_list.php');

class Panegyric_Admin_Plugin
{
    // class instance
    public static $instance;

    // class constructor
    public function __construct()
    {
        add_filter('set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3);
        add_action('admin_menu', [ $this, 'plugin_menu' ]);
    }

    public static function set_screen($status, $option, $value)
    {
        return $value;
    }

    public function plugin_menu()
    {
        $hook = add_management_page(
            'Panegyric Admin',
            'Panegyric Admin',
            'manage_options',
            'panegyric_admin',
            [ $this, 'plugin_settings_page' ]
        );

        add_action("load-$hook", [ $this, 'screen_option' ]);
    }

    /**
     * Plugin settings page
     */
    public function plugin_settings_page()
    {
        $action = isset($_GET['action']) ? $_GET['action'] : "";
        panegyric_info_list();
    }

    /**
     * Screen options
     */
    public function screen_option()
    {
        $action = isset($_GET['action']) ? $_GET['action'] : "";
        panegyric_info_list_screen_options();
    }

    /** Singleton instance */
    public static function get_instance()
    {
        if (! isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}

add_action('plugins_loaded', function () {
    Panegyric_Admin_Plugin::get_instance();
});

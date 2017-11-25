<?php

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

include(PLUGIN_PATH . 'admin/info_list.php');
include(PLUGIN_PATH . 'admin/tag_names_list.php');

class Panegyric_Admin_Plugin
{
    // class instance
    public static $instance;

    // tag names WP_List_Table object
    public $tag_names_obj;

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
        switch ($action) {
            case "edit":
                info_list($_GET['customer']);
                return;
                break;
        }
        $this->tag_names_obj->prepare_items(); ?>
        <div class="wrap">
            <h2><?= esc_html(get_admin_page_title()); ?></h2>

            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <form method="post">
                                <?php $this->tag_names_obj->display(); ?>
                            </form>
                        </div>
                    </div>
                </div>
                <br class="clear">
            </div>
        </div>
    <?php
    }

    /**
     * Screen options
     */
    public function screen_option()
    {
        $option = 'per_page';
        $args   = [
            'label'   => 'Organisations',
            'default' => 5,
            'option'  => 'organisations_per_page'
        ];

        add_screen_option($option, $args);

        $this->tag_names_obj = new Tag_Names_List();
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

<?php

include(PLUGIN_PATH . 'admin/ajax_list.php');
include(PLUGIN_PATH . 'admin/list_js.php');
include(PLUGIN_PATH . 'admin/org_list.php');
include(PLUGIN_PATH . 'admin/users_list.php');

function info_list()
{
    wp_nonce_field('ajax-custom-list-nonce', '_ajax_custom_list_nonce'); ?>
    <h3>Organisations</h3>
    <?php
    $org_table = new Organisations_List_Table();
    $org_table->prepare_items();
    $org_table->display(); ?>
    <!-- <h3>Users</h3> -->
    <?php
    // $users_table = new Users_List_Table();
    // $users_table->prepare_items();
    // $users_table->display();
    ?>
    <script language="javascript">
        <?= list_ajax("organisation", "org"); ?>
    </script>
    <?php
}

function info_list_screen_options()
{
    $option = 'per_page';
    $args   = [
        'label'   => 'Organisations',
        'default' => 5,
        'option'  => 'organisations_per_page'
    ];

    add_screen_option($option, $args);
}

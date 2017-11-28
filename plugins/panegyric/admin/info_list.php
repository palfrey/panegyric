<?php

include(PLUGIN_PATH . 'admin/org_list.php');
include(PLUGIN_PATH . 'admin/users_list.php');

function info_list()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        print "Post: ";
        print_r($_POST);
    } ?>
    <h3>Organisations</h3>
    <?php
    $org_table = new Organisations_List_Table();
    $org_table->prepare_items();
    $org_table->display();
    ?>
    <h3>Users</h3>
    <?php
    $users_table = new Users_List_Table();
    $users_table->prepare_items();
    $users_table->display();
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
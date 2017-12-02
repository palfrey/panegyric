<?php

include(PLUGIN_PATH . 'admin/ajax_list.php');
include(PLUGIN_PATH . 'admin/list_js.php');
include(PLUGIN_PATH . 'admin/org_list.php');
include(PLUGIN_PATH . 'admin/users_list.php');

if( isset( $_GET[ 'tab' ] ) ) {
    $active_tab = $_GET[ 'tab' ];
}
else {
    $active_tab = "organisations";
}

if ($active_tab == 'organisations') {
    Organisations_List_Table::setup_ajax();
}
else {
    Users_List_Table::setup_ajax();
}

function info_list()
{
    global $active_tab?>
    <h1>Panegyric Admin</h1>
    <div class="wrap">
        <h2 class="nav-tab-wrapper">
            <a href="?page=panegyric_admin&amp;tab=organisations" class="nav-tab <?php echo $active_tab == 'organisations' ? 'nav-tab-active' : ''; ?>">Organisations</a>
            <a href="?page=panegyric_admin&amp;tab=users" class="nav-tab <?php echo $active_tab == 'users' ? 'nav-tab-active' : ''; ?>">Users</a>
        </h2>
    </div><!-- /.wrap -->
    <?php
    if ($active_tab == 'organisations') { ?>
        <h3>Organisations</h3>
        <?php
        $org_table = new Organisations_List_Table();
        $org_table->prepare_items();
        $org_table->display();?>
        <script language="javascript">
            <?= list_ajax("organisation", "org"); ?>
        </script>
        <?php
    }
    else if ($active_tab == 'users') { ?>
        <h3>Users</h3>
        <?php
        $users_table = new Users_List_Table();
        $users_table->prepare_items();
        $users_table->display();?>
        <script language="javascript">
            <?= list_ajax("users", "user"); ?>
        </script>
        <?php
    }
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

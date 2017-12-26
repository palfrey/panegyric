<?php

include(PANEGYRIC_PLUGIN_PATH . 'admin/ajax_list.php');
include(PANEGYRIC_PLUGIN_PATH . 'admin/list_js.php');
include(PANEGYRIC_PLUGIN_PATH . 'admin/org_list.php');
include(PANEGYRIC_PLUGIN_PATH . 'admin/users_list.php');
include(PANEGYRIC_PLUGIN_PATH . 'admin/prs_list.php');

if (isset($_GET[ 'tab' ])) {
    $active_tab = $_GET[ 'tab' ];
} else {
    $active_tab = "organisations";
}

function panegyric_setup_ajax()
{
    // HACK: Workaround for "Undefined index: hook_suffix" in WP_Screen
    $GLOBALS['hook_suffix'] = '';

    $classes = array(
        "organisation" => "Organisations_List_Table",
        "users" => "Users_List_Table",
        "prs" => "PullRequests_List_Table"
    );
    AJAX_List_Table::setup_ajax($classes);
}

function panegyric_info_list()
{
    global $active_tab?>
    <h1>Panegyric Admin</h1>
    <div class="wrap">
        <h2 class="nav-tab-wrapper">
            <a href="?page=panegyric_admin&amp;tab=organisations" class="nav-tab <?php echo $active_tab == 'organisations' ? 'nav-tab-active' : ''; ?>">Organisations</a>
            <a href="?page=panegyric_admin&amp;tab=users" class="nav-tab <?php echo $active_tab == 'users' ? 'nav-tab-active' : ''; ?>">Users</a>
            <a href="?page=panegyric_admin&amp;tab=prs" class="nav-tab <?php echo $active_tab == 'prs' ? 'nav-tab-active' : ''; ?>">Pull Requests</a>
        </h2>
    </div><!-- /.wrap -->
    <?php
    if ($active_tab == 'organisations') {
        ?>
        <h3>Organisations</h3>
        <?php
        $org_table = new Organisations_List_Table();
        $org_table->prepare_items();
        $org_table->display(); ?>
        <script language="javascript">
            <?= list_ajax("organisation", "org"); ?>
        </script>
        <?php
    } elseif ($active_tab == 'users') {
        ?>
        <h3>Users</h3>
        <?php
        $users_table = new Users_List_Table();
        $users_table->prepare_items();
        $users_table->display(); ?>
        <script language="javascript">
            <?= list_ajax("users", "user"); ?>
        </script>
        <?php
    } elseif ($active_tab == 'prs') {
        ?>
        <h3>Users</h3>
        <?php
        $prs_table = new PullRequests_List_Table();
        $prs_table->prepare_items();
        $prs_table->display();
    }
}

function panegyric_info_list_screen_options()
{
    $option = 'per_page';
    $args   = [
        'label'   => 'Organisations',
        'default' => 5,
        'option'  => 'organisations_per_page'
    ];

    add_screen_option($option, $args);
}

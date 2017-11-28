<?php

class Organisations_List_Table extends WP_List_Table
{
    public function __construct($tag_name)
    {
        parent::__construct(array(
          'singular'=> 'Organisation',
          'plural' => 'Organisations',
          'ajax'   => false
        ));
        // WP_List_Table overrides __set so we need to do this crap
        $this->_args['tag_name'] = $tag_name;
    }

    public function get_columns()
    {
        return $columns= array(
           'org'=>__('Organisation'),
           'status'=>__('Status'),
           'updated'=>__('Updated'),
        );
    }

    public function no_items()
    {
        _e('No organisations found', 'sp');
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'org':
            case 'status':
            case 'updated':
                return $item[ $column_name ];
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $per_page     = $this->get_items_per_page('organisations_per_page', 5);
        $current_page = $this->get_pagenum();
        $total_items  = $this->record_count();

        $this->set_pagination_args([
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page'    => $per_page //WE have to determine how many items to show on a page
        ]);
        $this->items = $this->get_organisations();
    }

    public function record_count()
    {
        global $wpdb;
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}panegyric_org";
        return $wpdb->get_var($sql);
    }

    public function get_organisations()
    {
        global $wpdb;

        $sql = <<<EOT
            SELECT org.*
            FROM {$wpdb->prefix}panegyric_org org
            JOIN {$wpdb->prefix}panegyric_org_tag tag_org ON tag_org.org = org.org
            JOIN {$wpdb->prefix}panegyric_tag_names tn ON tn.name = tag_org.tag
            WHERE tn.name = '{$this->_args['tag_name']}'
EOT;
        if (! empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .= ! empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        }

        return $wpdb->get_results($sql, 'ARRAY_A');
    }
}

function info_list($name)
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        print "Post: ";
        print_r($_POST);
    }

    $org_table = new Organisations_List_Table($name);
    $orgs = join(",", array_map(function($org) {
        return $org['org'];
    }, $org_table->get_organisations()));
    $users = "";
    ?>
    <h2>Editing <?= $name ?></h2>
    <a href="<?= esc_url_raw(remove_query_arg('action')) ?>">Return to all tags list</a>
    <form action="<?= esc_url_raw(add_query_arg(array())) ?>" method="POST">
        <table border="0">
            <tr>
                <td><label for="org_list">Organisation list (comma separated)</label></td>
                <td><input name="org_list" type="text" value="<?= $orgs ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <td><label for="user_list">User list (comma separated)</label></td>
                <td><input name="user_list" type="text" value="<?= $users ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <td colspan="2"><input type="submit" class="button" value="Save" /></td>
            </tr>
        </table>
    </form>
    <h3>Organisations</h3>
    <?php
    $org_table->prepare_items();
    $org_table->display();
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
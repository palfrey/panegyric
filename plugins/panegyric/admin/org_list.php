<?php
class Organisations_List_Table extends AJAX_List_Table
{
    public function __construct()
    {
        parent::__construct(array(
          'singular'=> 'Organisation',
          'plural' => 'Organisations',
          'ajax'   => true
        ));
    }

    public function get_columns()
    {
        return $columns= array(
           'org'=>__('Organisation'),
           'status'=>__('Status'),
           'updated'=>__('Updated'),
        );
    }

    public function get_sortable_columns()
    {
        return array(
            'org' => array('org', true),
            'status' => array('status', true),
            'updated' => array('updated', true)
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
                return $item[ $column_name ];
            case 'updated':
                return $item[ $column_name ] ?: "Never";
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
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'orderby'   => $this->default_orderby('organisation'),
            'order'     => $this->default_order()
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

        $sql = "SELECT * FROM {$wpdb->prefix}panegyric_org";
        if (! empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .= ! empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        }
        //print_r($sql);

        return $wpdb->get_results($sql, 'ARRAY_A');
    }
}

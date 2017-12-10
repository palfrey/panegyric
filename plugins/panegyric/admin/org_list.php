<?php
class Organisations_List_Table extends AJAX_List_Table
{
    public function __construct()
    {
        parent::__construct(array(
          'table' => 'org',
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

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'org':
            case 'status':
                return $item[ $column_name ];
            case 'updated':
                return $item[ $column_name ] ?: "Never";
            default:
                return print_r($item, true); // Show the whole array for troubleshooting purposes
        }
    }

    public function prepare_items()
    {
        $this->prepare_items_core('org');
    }
}

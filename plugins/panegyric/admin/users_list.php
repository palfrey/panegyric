<?php
class Users_List_Table extends AJAX_List_Table
{
    public function __construct()
    {
        parent::__construct(array(
          'table' => 'users',
          'singular'=> 'User',
          'plural' => 'Users',
          'ajax'   => true
        ));
    }

    public function get_columns()
    {
        return $columns= array(
           'username' =>__('Username'),
           'name' =>__('Name'),
           'org'=>__('Organisation'),
           'status'=>__('Status'),
           'updated'=>__('Updated'),
           'prs_updated'=>__('PRs Updated'),
        );
    }

    public function get_sortable_columns()
    {
        return array(
            'username' => array('username', true),
            'org' => array('org', true),
            'updated' => array('updated', true)
        );
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'username':
            case 'name':
            case 'org':
            case 'status':
                return $item[ $column_name ];
            case 'updated':
            case 'prs_updated':
                return $item[ $column_name ] ?: "Never";
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    public function prepare_items()
    {
        $this->prepare_items_core('username');
    }
}

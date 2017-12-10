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
           'update'=>__('Update')
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
            case 'update':
                return "<a href=\"#\" class=\"update-link\" data-id=\"{$item['org']}\">Update now</a>";
            default:
                return print_r($item, true); // Show the whole array for troubleshooting purposes
        }
    }

    public function prepare_items()
    {
        $this->prepare_items_core('org');
    }

    public function update_item($org)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, "https://api.github.com/orgs/${org}/members");
        curl_setopt($ch, CURLOPT_USERAGENT, 'Panegyric');
        $json = curl_exec($ch);
        curl_close($ch);
        $obj = json_decode($json);

        $users = array();
        foreach ($obj as $user) {
            array_push($users, $user->login);
        }
        $db = new DB_Migrator();
        $db->update_org($org, $users);
    }
}

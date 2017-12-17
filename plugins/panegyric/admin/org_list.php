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
           'updated'=>__('Updated')
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
                $date = $item[ $column_name ] ?: "Never";
                return "<a href=\"#\" class=\"update-link\" data-id=\"{$item['org']}\">{$date}</a>";
            default:
                return print_r($item, true); // Show the whole array for troubleshooting purposes
        }
    }

    public function prepare_items()
    {
        $this->prepare_items_core('org');
    }

    public function update_item($kind, $org)
    {
        $ch = $this->curl_get("https://api.github.com/orgs/${org}/members");
        $json = curl_exec($ch);
        $db = new DB_Migrator();
        $info = curl_getinfo($ch);
        if ($info['http_code'] == 404) {
            $db->org_missing($org);
        } elseif ($info['http_code'] == 200) {
            $obj = json_decode($json);
            $users = array();
            foreach ($obj as $user) {
                array_push($users, $user->login);
            }
            $db->update_org($org, $users);
        } else {
            print_r($info);
        }
        curl_close($ch);
    }
}

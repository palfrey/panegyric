<?php
class Panegyric_Organisations_List_Table extends Panegyric_List_Table
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
           'delete'=>__("Delete Organisation")
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
                return "<a href=\"#\" class=\"update-link\" data-kind=\"org\" data-id=\"{$item['org']}\">{$date}</a>";
            case 'delete':
                return "<a href=\"#\" class=\"$column_name-link\" data-kind=\"delete\" data-id=\"{$item['org']}\">Delete</a>";
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
        switch ($kind) {
            case 'delete':
                $db = new Panegyric_DB_Migrator();
                $db->delete_org($org);
                break;
            case 'org':
                $response = wp_remote_get("https://api.github.com/orgs/${org}/members");
                $db = new Panegyric_DB_Migrator();
                $http_code = wp_remote_retrieve_response_code($response);
                if ($http_code == 404) {
                    $db->org_missing($org);
                } elseif ($http_code == 200) {
                    $json = wp_remote_retrieve_body($response);
                    $obj = json_decode($json);
                    $users = array();
                    foreach ($obj as $user) {
                        array_push($users, $user->login);
                    }
                    $db->update_org($org, $users);
                } else {
                    print_r($info);
                }
                break;
            }
    }
}

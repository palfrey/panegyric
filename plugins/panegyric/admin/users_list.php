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
                $date = $item[ $column_name ] ?: "Never";
                $kind = $column_name == 'updated' ? "user": "prs";
                return "<a href=\"#\" class=\"$column_name-link\" data-kind=\"$kind\" data-id=\"{$item['username']}\">{$date}</a>";
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    public function prepare_items()
    {
        $this->prepare_items_core('username');
    }

    public function update_item($id)
    {
        $kind = $_REQUEST['update_kind'];
        switch ($kind) {
            case 'user':
                $ch = $this->curl_get("https://api.github.com/users/$id");
                $json = curl_exec($ch);
                $db = new DB_Migrator();
                $info = curl_getinfo($ch);
                if ($info['http_code'] == 404) {
                    $db->user_missing($id);
                } elseif ($info['http_code'] == 200) {
                    $obj = json_decode($json);
                    $db->set_user_name($id, $obj->name);
                } else {
                    print_r($info);
                }
                curl_close($ch);
                break;
            default:
                print "Don't know how to update $kind";
        }
    }
}

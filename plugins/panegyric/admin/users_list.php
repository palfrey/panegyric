<?php
class Panegyric_Users_List_Table extends Panegyric_List_Table
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
           'delete'=>__("Delete User")
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
            case 'delete':
                return "<a href=\"#\" class=\"$column_name-link\" data-kind=\"delete\" data-id=\"{$item['username']}\">Delete</a>";
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    public function prepare_items()
    {
        $this->prepare_items_core('username');
    }

    public function update_item($kind, $id)
    {
        switch ($kind) {
            case 'delete':
                $db = new Panegyric_DB_Migrator();
                $db->delete_user($id);
                break;
            case 'user':
                $response = wp_remote_get("https://api.github.com/users/$id");
                $db = new Panegyric_DB_Migrator();
                $http_code = wp_remote_retrieve_response_code($response);
                if ($http_code == 404) {
                    $db->user_missing($id);
                } elseif ($http_code == 200) {
                    $json = wp_remote_retrieve_body($response);
                    $obj = json_decode($json);
                    $db->set_user_name($id, $obj->name);
                } else {
                    $db->user_denied($id);
                }
                break;
            case 'prs':
                $query = "is:pr author:$id is:public -user:$id is:merged";
                $query = str_replace(" ", "+", $query);
                $response = wp_remote_get("https://api.github.com/search/issues?&q=$query&sort=created&order=desc");
                $http_code = wp_remote_retrieve_response_code($response);
                $db = new Panegyric_DB_Migrator();
                if ($http_code == 200) {
                    $json = wp_remote_retrieve_body($ch);
                    $obj = json_decode($json);
                    foreach ($obj->items as $pr) {
                        if ($db->get_pr_by_url($pr->html_url) != null) {
                            continue;
                        }
                        $repo = $db->get_repo_by_url($pr->repository_url);
                        if ($repo == null) {
                            $rch = wp_remote_get($pr->repository_url);
                            $rhttp_code = wp_remote_retrieve_response_code($rch);
                            if ($rhttp_code == 200) {
                                $rjson = wp_remote_retrieve_body($rch);
                                $db->add_repo(json_decode($rjson));
                                $repo = $db->get_repo_by_url($pr->repository_url);
                            } else {
                                print("Repo get failure for {$pr->repository_url}");
                                print_r($rch);
                                continue;
                            }
                        }
                        $db->add_pr($pr, $repo, $id);
                    }
                    $db->prs_updated($id);
                } else {
                    $db->user_denied($id);
                }
                break;
            default:
                print "Don't know how to update $kind";
        }
    }
}

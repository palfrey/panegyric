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
                $db = new DB_Migrator();
                $db->delete_user($id);
                break;
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
            case 'prs':
                $query = "is:pr author:$id is:public -user:$id is:merged";
                $query = str_replace(" ", "+", $query);
                $ch = $this->curl_get("https://api.github.com/search/issues?&q=$query&sort=created&order=desc");
                $json = curl_exec($ch);
                $info = curl_getinfo($ch);
                $db = new DB_Migrator();
                if ($info['http_code'] == 200) {
                    $obj = json_decode($json);
                    foreach ($obj->items as $pr) {
                        if ($db->get_pr_by_url($pr->html_url) != null) {
                            continue;
                        }
                        $repo = $db->get_repo_by_url($pr->repository_url);
                        if ($repo == null) {
                            $rch = $this->curl_get($pr->repository_url);
                            $rjson = curl_exec($rch);
                            $rinfo = curl_getinfo($rch);
                            if ($rinfo['http_code'] == 200) {
                                $db->add_repo(json_decode($rjson));
                                $repo = $db->get_repo_by_url($pr->repository_url);
                            } else {
                                print("Repo get failure for {$pr->repository_url}");
                                print_r($rinfo);
                                continue;
                            }
                        }
                        $db->add_pr($pr, $repo, $id);
                    }
                    $db->prs_updated($id);
                } else {
                    print_r($info);
                }
                break;
            default:
                print "Don't know how to update $kind";
        }
    }
}

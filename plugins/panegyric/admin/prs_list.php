<?php
class PullRequests_List_Table extends AJAX_List_Table
{
    public function __construct()
    {
        parent::__construct(array(
          'table' => 'prs',
          'singular'=> 'Pull Request',
          'plural' => 'Pull Requests',
          'ajax'   => false
        ));
    }

    public function get_columns()
    {
        return $columns = array(
           'name' =>__('Repository'),
           'title' =>__('What'),
           'user' =>__('Who'),
           'when' =>__('When'),
        );
    }

    public function get_sortable_columns()
    {
        return array(
            'name' => array('name', true),
            'user' => array('user', true),
            'title' => array('title', true),
            'when' => array('%60when%60', true)
        );
    }

    public function get_records()
    {
        global $wpdb;

        $sql = "SELECT *, r.url as repo_url, pr.url as pr_url FROM {$wpdb->prefix}panegyric_prs pr join {$wpdb->prefix}panegyric_repo r on pr.repo = r.id";
        if (! empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .= ! empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        }

        return $wpdb->get_results($sql, 'ARRAY_A');
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'user':
            case 'when':
                return $item[ $column_name ];
            case 'name':
                return "<a href=\"{$item['repo_url']}\">{$item['name']}</a>";
            case 'title':
                return "<a href=\"{$item['pr_url']}\">{$item['title']}</a>";
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

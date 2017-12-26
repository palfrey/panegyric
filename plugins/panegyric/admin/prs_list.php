<?php
class PullRequests_List_Table extends Panegyric_List_Table
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
           'updated_at' =>__('When'),
        );
    }

    public function get_sortable_columns()
    {
        return array(
            'name' => array('name', true),
            'user' => array('user', true),
            'title' => array('title', true),
            'updated_at' => array('updated_at', true)
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
            case 'updated_at':
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
}

<?php

class Organisations_List_Table extends WP_List_Table
{
    public function __construct()
    {
        parent::__construct(array(
          'singular'=> 'wp_list_text_link',
          'plural' => 'wp_list_test_links',
          'ajax'   => true
          ));
    }

    function get_columns() {
        return $columns= array(
           'org'=>__('Organisation'),
           'status'=>__('Status'),
           'updated'=>__('Updated'),
        );
    }

    public function no_items()
    {
        _e('No organisations found', 'sp');
    }

    public function prepare_items()
    {
        $this->items = self::get_organisations();
    }

    public static function get_organisations()
    {
        global $wpdb;

        $sql = "SELECT * FROM {$wpdb->prefix}panegyric_org";

        if (! empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .= ! empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        }

        return $wpdb->get_results($sql, 'ARRAY_A');
    }
}

function info_list($name)
{
    ?>
    <h2>Editing <?= $name ?></h2>
    <a href="<?= esc_url_raw(remove_query_arg('action')) ?>">Return to main list</a><?php
    $org_table = new Organisations_List_Table();
    $org_table->prepare_items();
    $org_table->display();
}

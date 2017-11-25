<?php
class Tag_Names_List extends WP_List_Table
{
    /** Class constructor */
    public function __construct()
    {
        parent::__construct([
            'singular' => __('Customer', 'sp'), //singular name of the listed records
            'plural'   => __('Customers', 'sp'), //plural name of the listed records
            'ajax'     => false //does this table support ajax?
        ]);
    }

    /**
     * Retrieve customers data from the database
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public static function get_tag_names($per_page = 5, $page_number = 1)
    {
        global $wpdb;

        $sql = <<<EOT
            SELECT tn.name, IFNULL(GROUP_CONCAT(org.org),"") as org_list, IFNULL(GROUP_CONCAT(users.username), "") as user_list
            FROM {$wpdb->prefix}panegyric_tag_names tn
            LEFT JOIN {$wpdb->prefix}panegyric_org_tag tag_org ON tag_org.tag = tn.name
            LEFT JOIN {$wpdb->prefix}panegyric_org org ON tag_org.org = org.org
            LEFT JOIN {$wpdb->prefix}panegyric_user_tag tag_users ON tag_users.tag = tn.name
            LEFT JOIN {$wpdb->prefix}panegyric_users users ON tag_users.username = users.username
            GROUP BY tn.name
EOT;

        if (! empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .= ! empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        }

        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ($page_number - 1) * $per_page;

        $result = $wpdb->get_results($sql, 'ARRAY_A');

        return $result;
    }

    /**
     * Delete a customer record.
     *
     * @param int $id customer ID
     */
    public static function delete_tag_name($name)
    {
        global $wpdb;

        $wpdb->delete(
            "{$wpdb->prefix}panegyric_tag_names",
            [ 'name' => $name ],
            [ '%d' ]
        );
    }

    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public static function record_count()
    {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}panegyric_tag_names";

        return $wpdb->get_var($sql);
    }

    public function no_items()
    {
        _e('No tag names available. Try adding [github_prs org="&lt;your_org_name&gt;"] to a page', 'sp');
    }

    /**
     * Render a column when no column specific method exist.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'org_list':
            case 'user_list':
                return $item[ $column_name ];
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    /**
     * Render the bulk edit checkbox
     *
     * @param array $item
     *
     * @return string
     */
    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="bulk-delete[]" value="%s" />',
            $item['name']
        );
    }

    /**
     * Method for name column
     *
     * @param array $item an array of DB data
     *
     * @return string
     */
    public function column_name($item)
    {
        $delete_nonce = wp_create_nonce('sp_delete_tag_name');

        $title = '<strong>' . $item['name'] . '</strong>';

        $actions = [
            'edit' => sprintf('<a href="?page=%s&action=%s&customer=%s">Edit</a>', esc_attr($_REQUEST['page']), 'edit', $item['name']),
            'delete' => sprintf('<a href="?page=%s&action=%s&customer=%s&_wpnonce=%s">Delete</a>', esc_attr($_REQUEST['page']), 'delete', $item['name'], $delete_nonce)
        ];

        return $title . $this->row_actions($actions);
    }

    /**
     *  Associative array of columns
     *
     * @return array
     */
    public function get_columns()
    {
        $columns = [
            'cb'      => '<input type="checkbox" />',
            'name'    => __('Name', 'sp'),
            'org_list' => __('Organisations', 'sp'),
            'user_list'    => __('Extra Users', 'sp')
        ];

        return $columns;
    }

    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns()
    {
        $sortable_columns = array(
            'name' => array( 'name', true ),
            'org_list' => array( 'org_list', false )
        );

        return $sortable_columns;
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions()
    {
        $actions = [
            'bulk-delete' => 'Delete'
        ];

        return $actions;
    }

    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items()
    {
        $this->_column_headers = $this->get_column_info();

        /** Process bulk action */
        $this->process_bulk_action();

        $per_page     = $this->get_items_per_page('organisations_per_page', 5);
        $current_page = $this->get_pagenum();
        $total_items  = self::record_count();

        $this->set_pagination_args([
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page'    => $per_page //WE have to determine how many items to show on a page
        ]);

        $this->items = self::get_tag_names($per_page, $current_page);
    }

    public function process_bulk_action()
    {
        //Detect when a bulk action is being triggered...
        if ('delete' === $this->current_action()) {
            // In our file that handles the request, verify the nonce.
            $nonce = esc_attr($_REQUEST['_wpnonce']);

            if (! wp_verify_nonce($nonce, 'sp_delete_tag_name')) {
                die('Go get a life script kiddies');
            } else {
                self::delete_tag_name($_GET['customer']);

                // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
                echo '<script>window.location="' . esc_url_raw(remove_query_arg('action')) .'"; </script>';
                exit;
            }
        }

        // If the delete bulk action is triggered
        if ((isset($_POST['action']) && $_POST['action'] == 'bulk-delete')
             || (isset($_POST['action2']) && $_POST['action2'] == 'bulk-delete')
        ) {
            $delete_ids = esc_sql($_POST['bulk-delete']);

            // loop over the array of record IDs and delete them
            foreach ($delete_ids as $id) {
                self::delete_tag_name($id);
            }

            // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
            echo '<script>window.location="' . esc_url_raw(remove_query_arg('action')) .'"; </script>';
            exit;
        }
    }
}

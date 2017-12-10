<?php
class AJAX_List_Table extends WP_List_Table
{
    public static function setup_ajax($classes)
    {
        add_action('wp_ajax_ajax_fetch_custom_list', function () use ($classes) {
            $short = $_REQUEST['kind'];
            $class = $classes[$short];
            $obj = new $class();
            $obj->ajax_response();
        });
    }

    public function display()
    {
        wp_nonce_field('ajax-custom-list-nonce', '_ajax_custom_list_nonce');
        echo '<input id="order" type="hidden" name="order" value="' . $this->_pagination_args['order'] . '" />';
        echo '<input id="orderby" type="hidden" name="orderby" value="' . $this->_pagination_args['orderby'] . '" />';
        parent::display();
    }

    public function default_orderby($column)
    {
        return ! empty($_REQUEST['orderby']) && '' != $_REQUEST['orderby'] ? $_REQUEST['orderby'] : $column;
    }
    
    public function default_order()
    {
        return ! empty($_REQUEST['order']) && '' != $_REQUEST['order'] ? $_REQUEST['order'] : 'asc';
    }

    public function no_items()
    {
        _e('No '. strtolower($this->_args['singular']) .' found', 'sp');
    }

    public function record_count()
    {
        global $wpdb;
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}panegyric_{$this->_args['table']}";
        return $wpdb->get_var($sql);
    }

    public function get_records()
    {
        global $wpdb;

        $sql = "SELECT * FROM {$wpdb->prefix}panegyric_{$this->_args['table']}";
        if (! empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .= ! empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        }

        return $wpdb->get_results($sql, 'ARRAY_A');
    }

    public function prepare_items_core($default_column)
    {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $per_page     = $this->get_items_per_page(strtolower($this->_args['plural']) . '_per_page', 5);
        $current_page = $this->get_pagenum();
        $total_items  = $this->record_count();

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'orderby'   => $this->default_orderby($default_column),
            'order'     => $this->default_order()
        ]);
        $this->items = $this->get_records();
    }

    public function ajax_response()
    {
        check_ajax_referer('ajax-custom-list-nonce', '_ajax_custom_list_nonce');

        ob_start();

        if (! empty($_REQUEST['update_id'])) {
            $this->update_item($_REQUEST['update_id']);
        }
        $this->prepare_items();

        extract($this->_args);
        extract($this->_pagination_args, EXTR_SKIP);

        if (! empty($_REQUEST['no_placeholder'])) {
            $this->display_rows();
        } else {
            $this->display_rows_or_placeholder();
        }
        $rows = ob_get_clean();

        ob_start();
        $this->print_column_headers();
        $headers = ob_get_clean();

        ob_start();
        $this->pagination('top');
        $pagination_top = ob_get_clean();

        ob_start();
        $this->pagination('bottom');
        $pagination_bottom = ob_get_clean();

        $response = array( 'rows' => $rows );
        $response['pagination']['top'] = $pagination_top;
        $response['pagination']['bottom'] = $pagination_bottom;
        $response['column_headers'] = $headers;

        if (isset($total_items)) {
            $response['total_items_i18n'] = sprintf(_n('1 item', '%s items', $total_items), number_format_i18n($total_items));
        }

        if (isset($total_pages)) {
            $response['total_pages'] = $total_pages;
            $response['total_pages_i18n'] = number_format_i18n($total_pages);
        }

        die(json_encode($response));
    }
}

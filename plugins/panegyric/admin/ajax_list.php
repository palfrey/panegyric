<?php
function _ajax_fetch_custom_list_callback()
{
    $wp_list_table = new Organisations_List_Table();
    $wp_list_table->ajax_response();
}

add_action('wp_ajax_ajax_fetch_custom_list', '_ajax_fetch_custom_list_callback');

class AJAX_List_Table extends WP_List_Table
{
    public function display()
    {
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

    public function ajax_response()
    {
        check_ajax_referer('ajax-custom-list-nonce', '_ajax_custom_list_nonce');

        $this->prepare_items();

        extract($this->_args);
        extract($this->_pagination_args, EXTR_SKIP);

        ob_start();
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

<?php
function _ajax_fetch_custom_list_callback() {
    $wp_list_table = new Organisations_List_Table();
    $wp_list_table->ajax_response();
}

add_action('wp_ajax_ajax_fetch_custom_list', '_ajax_fetch_custom_list_callback');

class AJAX_List_Table extends WP_List_Table {
    public function __construct($args) {
        parent::__construct($args);
        $this->_args['short'] = strtolower($this->_args['singular']);
        $this->_args['order'] = $this->_args['short'] . '_order';
        $this->_args['orderby'] = $this->_args['short'] . '_orderby';
    }

    function default_orderby($column) {
        return ! empty( $_REQUEST[$this->_args['orderby']] ) && '' != $_REQUEST[$this->_args['orderby']] ? $_REQUEST[$this->_args['orderby']] : $column;
    }

    function default_order() {
        return ! empty( $_REQUEST[$this->_args['order']] ) && '' != $_REQUEST[$this->_args['order']] ? $_REQUEST[$this->_args['order']] : 'asc';
    }

    function display() {
        echo '<input id="' . $this->_args['order']. '" type="hidden" name="' . $this->_args['order'] . '" value="' . $this->_pagination_args[$this->_args['order']] . '" />';
        echo '<input id="' . $this->_args['orderby'] . '" type="hidden" name="' . $this->_args['orderby'] . '" value="' . $this->_pagination_args[$this->_args['orderby']] . '" />';
        parent::display();
    }

    public function print_column_headers( $with_id = true ) {
        list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();
        $current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
        $current_url = remove_query_arg( 'paged', $current_url );
        if ( isset( $_GET[$this->_args['orderby']] ) ) {
            $current_orderby = $_GET[$this->_args['orderby']];
        } else {
            $current_orderby = '';
        }
        if ( isset( $_GET[$this->_args['order']] ) && 'desc' === $_GET[$this->_args['order']] ) {
            $current_order = 'desc';
        } else {
            $current_order = 'asc';
        }
        if ( ! empty( $columns['cb'] ) ) {
            static $cb_counter = 1;
            $columns['cb'] = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __( 'Select All' ) . '</label>'
                . '<input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
            $cb_counter++;
        }
        foreach ( $columns as $column_key => $column_display_name ) {
            $class = array( 'manage-column', "column-$column_key" );
            if ( in_array( $column_key, $hidden ) ) {
                $class[] = 'hidden';
            }
            if ( 'cb' === $column_key )
                $class[] = 'check-column';
            elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ) ) )
                $class[] = 'num';
            if ( $column_key === $primary ) {
                $class[] = 'column-primary';
            }
            if ( isset( $sortable[$column_key] ) ) {
                list( $orderby, $desc_first ) = $sortable[$column_key];
                if ( $current_orderby === $orderby ) {
                    $order = 'asc' === $current_order ? 'desc' : 'asc';
                    $class[] = 'sorted';
                    $class[] = $current_order;
                } else {
                    $order = $desc_first ? 'desc' : 'asc';
                    $class[] = 'sortable';
                    $class[] = $desc_first ? 'asc' : 'desc';
                }
                $column_display_name = '<a href="' . esc_url( add_query_arg( compact( $this->_args['orderby'], $this->_args['order'] ), $current_url ) ) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
            }
            $tag = ( 'cb' === $column_key ) ? 'td' : 'th';
            $scope = ( 'th' === $tag ) ? 'scope="col"' : '';
            $id = $with_id ? "id='$column_key'" : '';
            if ( !empty( $class ) )
                $class = "class='" . join( ' ', $class ) . "'";
            echo "<$tag $scope $id $class>$column_display_name</$tag>";
        }
    }

    public function search_box( $text, $input_id ) {
        if ( empty( $_REQUEST['s'] ) && !$this->has_items() )
            return;
        $input_id = $input_id . '-search-input';
        if ( ! empty( $_REQUEST[$this->_args['orderby']] ) )
            echo '<input type="hidden" name="' . $this->_args['orderby'] . '" value="' . esc_attr( $_REQUEST[$this->_args['orderby']] ) . '" />';
        if ( ! empty( $_REQUEST[$this->_args['order']] ) )
            echo '<input type="hidden" name="' . $this->_args['order'] . '" value="' . esc_attr( $_REQUEST[$this->_args['order']] ) . '" />';
        if ( ! empty( $_REQUEST['post_mime_type'] ) )
            echo '<input type="hidden" name="post_mime_type" value="' . esc_attr( $_REQUEST['post_mime_type'] ) . '" />';
        if ( ! empty( $_REQUEST['detached'] ) )
            echo '<input type="hidden" name="detached" value="' . esc_attr( $_REQUEST['detached'] ) . '" />';
        ?>
    <p class="search-box">
        <label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo $text; ?>:</label>
        <input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" />
        <?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
    </p>
    <?php
    }

    function ajax_response() {
        check_ajax_referer( 'ajax-custom-list-nonce', '_ajax_custom_list_nonce' );

        $this->prepare_items();

        extract( $this->_args );
        extract( $this->_pagination_args, EXTR_SKIP );

        ob_start();
        if ( ! empty( $_REQUEST['no_placeholder'] ) )
            $this->display_rows();
        else
            $this->display_rows_or_placeholder();
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

        if ( isset( $total_items ) )
            $response['total_items_i18n'] = sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) );

        if ( isset( $total_pages ) ) {
            $response['total_pages'] = $total_pages;
            $response['total_pages_i18n'] = number_format_i18n( $total_pages );
        }

        die( json_encode( $response ) );
    }
}
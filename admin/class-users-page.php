<?php
defined( 'ABSPATH' ) or die();

if ( ! class_exists( 'UsersAdminList' ) ) {

    /**
     * Class UsersAdminList
     * @desc Class is responsible for a users listing page in the admin.
     */
    class UsersAdminList {

        var $per_page = 10;
        var $found_items;
        var $total_found = 0;
        var $total_pages = 0;
        var $roles = array();
        var $role = '';
        var $page = 1;
        var $orderby = 'user_name';
        var $order = 'asc';
        var $available_order_columns = [ 'user_name', 'display_name' ];

        public function __construct () {
            add_action( 'admin_menu', [ $this, 'admin_page_init' ] );
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ], 10 );
            add_action( 'admin_print_footer_scripts', [ $this, 'footer_code' ], 20 );
            add_action( 'wp_loaded', [ $this, 'prepare_roles' ] );
            add_action( 'wp_ajax_load_users', [ $this, 'load_users' ] );
        }

        /**
         * Adds required JS & CSS files via Wordpress API
         */
        public function enqueue_scripts () {
            wp_enqueue_style( 'ct-admin-list', CTAL_URL.'/admin/css/ct-admin-list.css' );
            wp_enqueue_script( 'ct-admin-list', CTAL_URL.'/admin/js/ct-admin-list.js', [ 'jquery' ], NULL, true );
        }

        public function footer_code () {
            ?>
            <script type="text/javascript">
                ct_current_role = '<?php echo $this->role; ?>';
                ct_current_orderby = '<?php echo $this->orderby; ?>';
                ct_current_order = '<?php echo $this->order; ?>';
                ct_current_page = <?php echo $this->page; ?>;
                ct_total_pages = <?php echo $this->total_pages; ?>;
            </script>
            <?php
        }

        /**
         * Adds admin page & top-level menu item
         */
        public function admin_page_init () {
            add_menu_page( __( 'Users List', 'ct-admin-list' ), __( 'Users list', 'ct-admin-list' ), 'list_users', 'ct-users-list', [ $this, 'list_page_content' ] );
        }

        /**
         * Outputs plugin option page. Callback for add_options_page function
         */
        public function list_page_content () {
            // Check permissions
            if ( ! current_user_can( 'list_users' ) ) {
                return;
            }

            $user_query = $this->list_query_users();

            $sort_link_username = $this->sort_link( 'user_name', $this->orderby, $this->order );
            $sort_link_displayname = $this->sort_link( 'display_name', $this->orderby, $this->order );

            include CTAL_PATH.'/admin/templates/users-page.php';
        }

        public function load_users () {
            // Check if user has permissions to view data
            if ( ! current_user_can( 'list_users' ) ) {
                return;
            }

            $users_data = [];

            $user_query = $this->list_query_users();
            if ( $user_query->get_results() ) {
                foreach ($user_query->get_results() as $user) {
                    $users_data[] = [
                        'user_name'    => $user->user_login,
                        'user_link'    => get_edit_user_link( $user->ID ),
                        'display_name' => $user->display_name,
                        'user_email'   => $user->user_email,
                        'user_roles'   => $this->format_roles( $user->roles )
                    ];
                }
            }

            $result['found_items'] = $users_data;
            $result['total_found'] = $this->total_found;
            $result['total_found_formatted'] = sprintf( _n( '%s item', '%s items', $this->total_found, 'ct-admin-list' ), number_format_i18n( $this->total_found ) );
            $result['total_pages'] = $this->total_pages;
            $result['total_pages_formatted'] = number_format_i18n( $this->total_pages );

            wp_send_json( $result );
        }

        private function list_query_users () {
            $orderby = $this->postget( 'orderby' );
            if ( ! in_array( $orderby, $this->available_order_columns ) ) {
                $orderby = $this->orderby;
            }
            $this->orderby = $orderby;

            $order = $this->postget( 'order' );
            if ( ! in_array( $order, [ 'asc', 'desc' ] ) ) {
                $order = $this->order;
            }
            $this->order = $order;

            $role = $this->postget( 'role' );
            if ( ! $role ) {
                $role__in = array();
            } else {
                $role__in = (array)$role;
            }
            $this->role = $role;

            $current_page = $this->postget( 'paged' );
            if ( ! (int)$current_page ) {
                $current_page = 1;
            }
            $this->page = (int)$current_page;

            $offset = $this->per_page * $current_page - $this->per_page;

            $args = array(
                'count_total' => true,
                'offset'      => $offset,
                'number'      => $this->per_page,
                'role__in'    => $role__in,
                'orderby'     => $orderby,
                'order'       => $order
            );

            // The Query
            $query = new WP_User_Query( $args );

            $this->found_items = $query->get_results();
            $this->total_found = $query->get_total();
            $this->total_pages = ceil( $this->total_found / $this->per_page );

            return $query;
        }

        /**
         * Gets the list of defined WP roles and creates an array with each role's data
         */
        public function prepare_roles () {
            $roles = [];
            $available_roles = count_users();
            $wp_roles = wp_roles();
            foreach ($wp_roles->role_names as $role_name => $role_title) {
                if ( array_key_exists( $role_name, $available_roles['avail_roles'] ) && (int)$available_roles['avail_roles'][ $role_name ] ) {

                    $roles[ $role_name ] = [
                        'name'  => $role_name,
                        'title' => translate_user_role( $role_title ),
                        'count' => (int)$available_roles['avail_roles'][ $role_name ]
                    ];
                }
            }
            $this->roles = $roles;
        }

        /**
         * Return sort link with current filter options
         */
        public function sort_link ( $orderby, $current_orderby, $current_order ) {
            if ( $orderby == $current_orderby ) {
                $order = $current_order == 'asc' ? 'desc' : 'asc';
            } else {
                $order = 'asc';
            }

            $query_string = add_query_arg( 'orderby', $orderby, $_SERVER['QUERY_STRING'] );
            $query_string = add_query_arg( 'order', $order, $query_string );
            $query_string = remove_query_arg( 'paged', $query_string );

            return admin_url( 'admin.php' ).'?'.$query_string;
        }

        public function prepare_sortable_classes ( $orderby ) {
            if ( $orderby == $this->orderby ) {
                $class[] = 'sorted';

                if ( $this->order == 'desc' ) {
                    $class[] = 'desc';
                } else {
                    $class[] = 'asc';
                }
            } else {
                $class[] = 'sortable desc';
            }

            return join( ' ', $class );
        }

        public function prepare_new_orderdir ( $orderby ) {
            if ( $orderby == $this->orderby ) {
                if ( $this->order == 'desc' ) {
                    $order = 'asc';
                } else {
                    $order = 'desc';
                }
            } else {
                $order = 'asc';
            }

            return $order;
        }

        public function prepare_pagination_select_options () {
            if ( ! $this->total_pages ) {
                return NULL;
            }

            $out = '';
            for( $x = 1; $x <= $this->total_pages; $x++ ){
                if( $x === $this->page ){
                    $selected = 'selected="selected"';
                } else {
                    $selected = '';
                }
                $out .= '<option value="'.$x.'" '.$selected.'>'.$x.'</option>';
            }

            return $out;
        }

        /**
         * Return role link with current sorting options
         */
        public function role_link ( $role = '' ) {
            $query_string = remove_query_arg( 'paged', $_SERVER['QUERY_STRING'] );
            if ( $role ) {
                $query_string = add_query_arg( 'role', $role, $query_string );
            } else {
                $query_string = remove_query_arg( 'role', $query_string );
            }
            return admin_url( 'admin.php' ).'?'.$query_string;
        }

        /**
         * Returns roles filter navigation
         */
        public function role_filter_nav () {
            if ( ! $this->roles ) {
                return NULL;
            }

            $current_role = $this->postget( 'role' );

            $output = '<li><a href="'.esc_url( $this->role_link() ).'" class="'.( ! $current_role ? 'current' : '' ).'" data-filter-role="">'.esc_attr__( 'All', 'ct-admin-list' ).' <span class="count">('.$this->total_found.')</span></a> |</li> ';

            foreach ($this->roles as $role) {
                if ( $role['name'] == $current_role ) {
                    $current_class = 'current';
                } else {
                    $current_class = '';
                }
                $output .= '<li><a href="'.esc_url( $this->role_link( $role['name'] ) ).'" class="'.$current_class.'" data-filter-role="'.$role['name'].'">'.$role['title'].' <span class="count">('.$role['count'].')</span></a> <span class="separator">|</span></li> ';
            }

            return $output;
        }

        /**
         * Return paginator generated content
         */
        public function paginator () {
            if ( ! $this->total_found ) {
                return NULL;
            }

            $query_string = $_SERVER['QUERY_STRING'];
            $base_url = admin_url( 'admin.php' ).'?';

            if ( (int)$this->total_pages === 1 ) {
                $paginator_class = 'one-page';
            } else {
                $paginator_class = '';
            }

            $output = '<div class="tablenav-pages '.$paginator_class.'">';

            $output .= '<span class="displaying-num">' . sprintf( _n( '%s item', '%s items', $this->total_found, 'ct-admin-list' ), number_format_i18n( $this->total_found ) ) . '</span>';
            $output .= '<span class="pagination-links">';

            // Disable/enable first page link
            if ( $this->page <= 2 ) {
                $disabled = 'disabled="disabled"';
            } else {
                $disabled = '';
            }

            // First link html
            $output .= sprintf( '<a class="first-page" %s href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></a> ',
                $disabled,
                esc_url( $base_url.add_query_arg( 'paged', 1, $query_string ) ),
                __( 'First page', 'ct-admin-list' ),
                '&laquo;'
            );

            // Disable/enable previous page link
            if ( $this->page <= 1 ) {
                $disabled = 'disabled="disabled"';
            } else {
                $disabled = '';
            }

            // Previous link html
            $output .= sprintf( '<a class="prev-page" %s href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></a> ',
                $disabled,
                esc_url( $base_url.add_query_arg( 'paged', $this->page - 1, $query_string ) ),
                __( 'Previous page', 'ct-admin-list' ),
                '&lsaquo;'
            );

            $output .= '<span class="paging-input">';
            $output .= '<label for="current-page-selector" class="screen-reader-text">' . __( 'Current Page', 'ct-admin-list' ) . '</label>';

            $output .= '<select name="paged" class="current-page-selector">'.$this->prepare_pagination_select_options().'</select>';

            $html_total_pages = sprintf( '<span class="total-pages">%s</span>', number_format_i18n( $this->total_pages ) );
            $output .= sprintf( _x( '%1$s of %2$s', 'paging', 'ct-admin-list' ),
                '<span class="tablenav-paging-text">',
                $html_total_pages ) . '</span></span> ';

            // Disable/enable next page link
            if ( $this->page + 1 > $this->total_pages ) {
                $disabled = 'disabled="disabled"';
            } else {
                $disabled = '';
            }

            // Next link html
            $output .= sprintf( '<a class="next-page" %s href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></a> ',
                $disabled,
                esc_url( $base_url.add_query_arg( 'paged', $this->page + 1, $query_string ) ),
                __( 'Next page', 'ct-admin-list' ),
                '&rsaquo;'
            );

            // Disable/enable next page link
            if ( $this->page + 2 > $this->total_pages ) {
                $disabled = 'disabled="disabled"';
            } else {
                $disabled = '';
            }

            // Last link html
            $output .= sprintf( '<a class="last-page" %s href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></a> ',
                $disabled,
                esc_url( $base_url.add_query_arg( 'paged', $this->total_pages, $query_string ) ),
                __( 'Last page', 'ct-admin-list' ),
                '&raquo;'
            );

            $output .= '</div>';

            return $output;
        }

        /**
         * Formats array with roles for displaying
         */
        public function format_roles ( $role_array ) {
            if ( ! $role_array || ! is_array( $role_array ) ) {
                return NULL;
            }
            $array = [];
            foreach ($role_array as $role_name) {
                $array[] = $this->roles[ $role_name ]['title'];
            }
            if ( $array ) {
                return join( ', ', $array );
            } else {
                return NULL;
            }
        }

        private function postget ( $key ) {
            if ( isset( $_POST[ $key ] ) ) {
                return filter_input( INPUT_POST, $key );
            } elseif ( isset( $_GET[ $key ] ) ) {
                return filter_input( INPUT_GET, $key );
            } else {
                return NULL;
            }
        }

    }

    new UsersAdminList();
}

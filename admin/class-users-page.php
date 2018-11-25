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
        var $roles = array();
        var $role = '';
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
            $this->found_items = $user_query->get_results();
            $this->total_found = $user_query->get_total();

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
            $result['total_found'] = $user_query->get_total();

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
            return new WP_User_Query( $args );
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
            $current_page = (int)$this->postget( 'paged' );
            if ( ! (int)$current_page ) {
                $current_page = 1;
            }

            $total_pages = ceil( $this->total_found / $this->per_page );

            if ( $total_pages === 1 ) {
                return NULL;
            }

            // Set pages range to display
            $from = $current_page > 4 ? $current_page - 4 : 1;
            $to = $current_page < $total_pages - 4 ? $current_page + 4 : $total_pages;

            $output = '<span class="displaying-num">'.$this->format_total_found( $this->total_found ).'</span>';
            $output .= '<span class="pagination-links">';

            // Add first page link or label
            if ( $current_page <= 2 ) {
                $output .= '<span class="tablenav-pages-navspan" aria-hidden="true">&laquo;</span> ';
            } else {
                $output .= sprintf( '<a class="first-page" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></a> ',
                    esc_url( $base_url.add_query_arg( 'paged', 1, $query_string ) ),
                    __( 'First page', 'ct-admin-list' ),
                    '&laquo;'
                );
            }

            // Add previous page link or label
            if ( $current_page <= 1 ) {
                $output .= '<span class="tablenav-pages-navspan" aria-hidden="true">&lsaquo;</span> ';
            } else {
                $output .= sprintf( '<a class="prev-page" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></a> ',
                    esc_url( $base_url.add_query_arg( 'paged', $current_page - 1, $query_string ) ),
                    __( 'Previous page', 'ct-admin-list' ),
                    '&lsaquo;'
                );
            }

            $output .= '<span class="numbered-links">';

            for ($x = $from; $x <= $to; $x++) {
                if ( $x === $current_page ) {
                    $output .= '<span class="current-page">'.$x.'</span> ';
                } else {
                    $output .= '<a href="'.esc_url( $base_url.add_query_arg( 'paged', $x, $query_string ) ).'"><span>'.$x.'</span></a> ';
                }
            }

            $output .= '</span>';

            // Add next page link or label
            if ( $current_page + 1 >= $total_pages ) {
                $output .= '<span class="tablenav-pages-navspan" aria-hidden="true">&rsaquo;</span> ';
            } else {
                $output .= sprintf( '<a class="prev-page" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></a> ',
                    esc_url( $base_url.add_query_arg( 'paged', $current_page + 1, $query_string ) ),
                    __( 'Next page', 'ct-admin-list' ),
                    '&rsaquo;'
                );
            }

            // Add last page link or label
            if ( $current_page + 2 >= $total_pages ) {
                $output .= '<span class="tablenav-pages-navspan" aria-hidden="true">&raquo;</span> ';
            } else {
                $output .= sprintf( '<a class="prev-page" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></a> ',
                    esc_url( $base_url.add_query_arg( 'paged', $total_pages, $query_string ) ),
                    __( 'Last page', 'ct-admin-list' ),
                    '&raquo;'
                );
            }

            if ( $total_pages ) {
                $page_class = $total_pages < 2 ? ' one-page' : '';
            } else {
                $page_class = ' no-pages';
            }
            $this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

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

        /**
         * Formats total users count
         */
        public function format_total_found ( $number ) {
            return sprintf( '%d %s', number_format_i18n( $number ), _n( 'user', 'users', $number, 'ct-admin-list' ) );
        }

    }

    new UsersAdminList();
}

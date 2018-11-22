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

        public function __construct () {
            add_action( 'admin_menu', [ $this, 'admin_page_init' ] );
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        }

        /**
         * Adds required JS & CSS files via Wordpress API
         */
        public function enqueue_scripts () {
            wp_enqueue_style( 'ct-admin-list', CTAL_URL.'/admin/css/ct-admin-list.css' );
            wp_enqueue_script( 'ct-admin-list', CTAL_URL.'/admin/js/ct-admin-list.js', [ 'jquery' ], NULL, true );
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

            $role__in = (array)filter_input( INPUT_GET, 'role' );
            if( ! $role__in ){
                $role__in = null;
            }
            $current_page = filter_input( INPUT_GET, 'paged' );
            if ( ! (int)$current_page ) {
                $current_page = 1;
            }
            $offset = $this->per_page * $current_page - $this->per_page;

            $args = array(
                'count_total' => true,
                'offset'      => $offset,
                'number'      => $this->per_page,
                'role__in'      => $role__in
            );

            // The Query
            $user_query = new WP_User_Query( $args );

            $this->found_items = $user_query->get_results();
            $this->total_found = $user_query->get_total();
            $this->roles = $this->prepare_roles();

            include CTAL_PATH.'/admin/templates/users-page.php';
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
            return $roles;
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
            if( ! $this->roles ){
                return null;
            }

            $current_role = filter_input( INPUT_GET, 'role' );

            $output = '<li><a href="'.esc_url( $this->role_link() ).'" class="'.(!$current_role?'current':'').'">'.esc_attr__('All', 'ct-admin-list').' <span class="count">('.$this->total_found.')</span></a> |</li> ';

            foreach( $this->roles as $role ){
                if( $role['name'] == $current_role ){
                    $current_class = 'current';
                } else {
                    $current_class = '';
                }
                $output .= '<li><a href="'.esc_url( $this->role_link($role['name']) ).'" class="'.$current_class.'">'.$role['title'].' <span class="count">('.$role['count'].')</span></a> <span class="separator">|</span></li> ';
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
            $current_page = (int)filter_input( INPUT_GET, 'paged' );
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

        /**
         * Formats total users count
         */
        public function format_total_found ( $number ) {
            return sprintf( '%d %s', number_format_i18n( $number ), _n( 'user', 'users', $number, 'ct-admin-list' ) );
        }

    }

    new UsersAdminList();
}

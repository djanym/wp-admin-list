<?php
defined( 'ABSPATH' ) or die();

if( ! class_exists('UsersAdminList') ){

	/**
	 * Class UsersAdminList
	 * @desc Class is responsible for a users listing page in the admin.
	 */
	class UsersAdminList {

	    var $per_page = 10;
	    var $found_items;
	    var $total_found;

		public function __construct(){
			add_action('admin_menu', [ $this, 'admin_page_init' ]);
			add_action('admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		}

		/**
		 * Adds required JS & CSS files via Wordpress API
		 */
		public function enqueue_scripts(){
			wp_enqueue_style( 'ct-admin-list', CTAL_URL . '/admin/css/ct-admin-list.css' );
			wp_enqueue_script( 'ct-admin-list', CTAL_URL . '/admin/js/ct-admin-list.js', ['jquery'], null, true );
		}

		/**
		 * Adds admin page & top-level menu item
		 */
		public function admin_page_init(){
			add_menu_page(__('Users List', 'ct-admin-list'), __('Users list', 'ct-admin-list'), 'list_users', 'ct-users-list', [ $this, 'list_page_content']);
		}

		/**
		 * Outputs plugin option page. Callback for add_options_page function
		 */
		public function list_page_content(){
			// Check permissions
			if( ! current_user_can('list_users') ){
				return;
			}

            $current_page = filter_input(INPUT_GET, 'paged');
            if( ! (int)$current_page ){
                $current_page = 1;
            }
            $offset = $this->per_page * $current_page - $this->per_page;

            $args = array(
                'count_total' => true,
                'offset' => $offset,
                'number' => $this->per_page
            );

            // The Query
            $user_query = new WP_User_Query( $args );

            $this->found_items = $user_query->get_results();
			$this->total_found = $user_query->get_total();

			include CTAL_PATH . '/admin/templates/users-page.php';
		}

		/**
		 * Return paginator generated content
		 */
		public function paginator(){
		    if( ! $this->total_found ){
		        return null;
            }

            $query_string = $_SERVER['QUERY_STRING'];
            $base_url = admin_url('admin.php') . '?';
            $current_page = (int)filter_input(INPUT_GET, 'paged');
            if( ! (int)$current_page ){
                $current_page = 1;
            }

            $total_pages = ceil($this->total_found / $this->per_page);

            if( $total_pages === 1 ){
                return null;
            }

            // Set pages range to display
            $from = $current_page > 4 ? $current_page - 4 : 1;
            $to = $current_page < $total_pages - 4 ? $current_page + 4 : $total_pages;

            $output = '<span class="displaying-num">' . $this->format_total_found($this->total_found) . '</span>';
            $output .= '<span class="pagination-links">';

            // Add first page link or label
            if ( $current_page <= 2 ) {
                $output .= '<span class="tablenav-pages-navspan" aria-hidden="true">&laquo;</span> ';
            } else {
                $output .= sprintf( '<a class="first-page" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></a> ',
                    esc_url($base_url . add_query_arg('paged', 1, $query_string) ),
                    __( 'First page', 'ct-admin-list' ),
                    '&laquo;'
                );
            }

            // Add previous page link or label
            if ( $current_page <= 1 ) {
                $output .= '<span class="tablenav-pages-navspan" aria-hidden="true">&lsaquo;</span> ';
            } else {
                $output .= sprintf( '<a class="prev-page" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></a> ',
                    esc_url($base_url . add_query_arg('paged', $current_page - 1, $query_string) ),
                    __( 'Previous page', 'ct-admin-list' ),
                    '&lsaquo;'
                );
            }

            $output .= '<span class="numbered-links">';

            for( $x = $from; $x <= $to; $x++ ){
                if( $x === $current_page ){
                    $output .= '<span class="current-page">'.$x.'</span> ';
                } else {
                    $output .= '<a href="'.esc_url($base_url . add_query_arg('paged', $x, $query_string) ).'"><span>'.$x.'</span></a> ';
                }
            }

            $output .= '</span>';

            // Add next page link or label
            if ( $current_page + 1 >= $total_pages ) {
                $output .= '<span class="tablenav-pages-navspan" aria-hidden="true">&rsaquo;</span> ';
            } else {
                $output .= sprintf( '<a class="prev-page" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></a> ',
                    esc_url($base_url . add_query_arg('paged', $current_page + 1, $query_string) ),
                    __( 'Next page', 'ct-admin-list' ),
                    '&rsaquo;'
                );
            }

            // Add last page link or label
            if ( $current_page + 2 >= $total_pages ) {
                $output .= '<span class="tablenav-pages-navspan" aria-hidden="true">&raquo;</span> ';
            } else {
                $output .= sprintf( '<a class="prev-page" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">%s</span></a> ',
                    esc_url($base_url . add_query_arg('paged', $total_pages, $query_string) ),
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
		public function format_roles($array){
		    if( ! $array || ! is_array($array) ){
		        return null;
            }
			return join(', ', $array);
		}

		/**
		 * Formats total users count
		 */
		public function format_total_found($number){
		    return sprintf('%d %s', number_format_i18n( $number ), _n('user', 'users', $number, 'ct-admin-list') );
		}

	}

	new UsersAdminList();
}

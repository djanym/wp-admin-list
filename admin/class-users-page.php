<?php
defined( 'ABSPATH' ) or die();

if( ! class_exists('UsersAdminList') ){

	/**
	 * Class UsersAdminList
	 * @desc Class is responsible for a users listing page in the admin.
	 */
	class UsersAdminList {

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

			include CTAL_PATH . '/admin/templates/users-page.php';
		}

	}

	new UsersAdminList();
}

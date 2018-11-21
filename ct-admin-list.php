<?php
/*
Plugin Name:  Codeable Test: Users Listing
Description:  Adds admin page with users listing.
Version:      1.0
Author:       Nael Concescu
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  ct-admin-list
Domain Path:  /languages
*/

defined( 'ABSPATH' ) or die();

// Wrap plugin absolute path into a constant
define('CTAL_PATH', plugin_dir_path( __FILE__ ) );
// Wrap plugin absolute path into a constant
define('CTAL_URL', plugin_dir_url( __FILE__ ) );

if ( is_admin() ) {
	include CTAL_PATH.'/admin/class-users-page.php';
}

/**
 * Activates internationalization feature for the plugin
 */
function ct_plugin_textdomain(){
	load_plugin_textdomain('ct-admin-list', false, CTAL_PATH.'/languages/');
}
add_action('plugins_loaded', 'ct_plugin_textdomain');
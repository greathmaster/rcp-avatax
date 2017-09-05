<?php
/**
 * Plugin Name: Restrict Content Pro - AvaTax
 * Plugin URL: https://skilledcode.com/plugins/rcp-avatax
 * Description: Avatax add-on for Restrict Content Pro
 * Version: 1.0.0
 * Author: Tanner Moushey
 * Author URI: https://skilledcode.com
 * Text Domain: rcp-avatax
 * Domain Path: languages
 */

if ( !defined( 'RCP_AVATAX_PLUGIN_DIR' ) ) {
	define( 'RCP_AVATAX_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( !defined( 'RCP_AVATAX_PLUGIN_URL' ) ) {
	define( 'RCP_AVATAX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( !defined( 'RCP_AVATAX_PLUGIN_FILE' ) ) {
	define( 'RCP_AVATAX_PLUGIN_FILE', __FILE__ );
}
if ( !defined( 'RCP_AVATAX_PLUGIN_VERSION' ) ) {
	define( 'RCP_AVATAX_PLUGIN_VERSION', '1.0.0' );
}
if ( ! defined( 'CAL_GREGORIAN' ) ) {
	define( 'CAL_GREGORIAN', 1 );
}

// EDD Licensing constants
define( 'RCP_AVATAX_STORE_URL', 'https://skillfulplugins.com' );
define( 'RCP_AVATAX_ITEM_NAME', 'Restrict Content Pro - AvaTax' );

require_once( RCP_AVATAX_PLUGIN_DIR . 'vendor/autoload.php' );

/**
 * @var RCP_Avatax\Init
 */
global $rcp_avatax;

if( version_compare( PHP_VERSION, '5.3', '<' ) ) {

} else {
	$rcp_avatax = rcp_avatax();
}

/**
 * Load plugin text domain for translations.
 *
 * @return void
 */
function rcp_avatax_load_textdomain() {

	// Set filter for plugin's languages directory
	$rcp_lang_dir = dirname( plugin_basename( RCP_AVATAX_PLUGIN_FILE ) ) . '/languages/';
	$rcp_lang_dir = apply_filters( 'rcp_avatax_languages_directory', $rcp_lang_dir );


	// Traditional WordPress plugin locale filter

	$get_locale = get_locale();

	if ( function_exists( 'rcp_compare_wp_version' ) && rcp_compare_wp_version( 4.7 ) ) {
		$get_locale = get_user_locale();
	}

	/**
	 * Defines the plugin language locale used in RCP.
	 *
	 * @var string $get_locale The locale to use. Uses get_user_locale()` in WordPress 4.7 or greater,
	 *                  otherwise uses `get_locale()`.
	 */
	$locale        = apply_filters( 'plugin_locale',  $get_locale, 'rcp-avatax' );
	$mofile        = sprintf( '%1$s-%2$s.mo', 'rcp-avatax', $locale );

	// Setup paths to current locale file
	$mofile_local  = $rcp_lang_dir . $mofile;
	$mofile_global = WP_LANG_DIR . '/rcp-avatax/' . $mofile;

	if ( file_exists( $mofile_global ) ) {
		// Look in global /wp-content/languages/rcp folder
		load_textdomain( 'rcp-avatax', $mofile_global );
	} elseif ( file_exists( $mofile_local ) ) {
		// Look in local /wp-content/plugins/easy-digital-downloads/languages/ folder
		load_textdomain( 'rcp-avatax', $mofile_local );
	} else {
		// Load the default language files
		load_plugin_textdomain( 'rcp-avatax', false, $rcp_lang_dir );
	}

}
add_action( 'init', 'rcp_avatax_load_textdomain' );

/**
 * @return \RCP_Avatax\Init
 */
function rcp_avatax() {
	return RCP_Avatax\Init::get_instance();
}

/**
 * Helper function to get array parameters when they might not exist
 *
 * @param        $array
 * @param        $key
 * @param string $default
 *
 * @return string
 */
function rcp_avatax_param_get( $array, $key, $default = '' ) {
	if ( empty( $array[ $key ] ) ) {
		return $default;
	}

	return apply_filters( 'rcp_avatax_param_get', $array[ $key ], $array, $key, $default );
}
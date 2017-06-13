<?php

namespace RCP_Avatax;

use RCP_Avatax\AvaTax\API;
use RCP_Avatax\Admin\Levels;
use SkilledCode\Helpers;

class Init {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * @var HandleTaxes
	 */
	public $handle_taxes;

	/**
	 * @var MemberFields
	 */
	public $member_fields;

	/**
	 * @var Logging
	 */
	public $logging;

	/**
	 * Only make one instance of \RCP_Avatax\Init
	 *
	 * @return Init
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Init ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_action( 'plugins_loaded', array( $this, 'maybe_setup' ), - 9999 );
	}


	public function maybe_setup() {
		if ( ! $this->check_required_plugins() ) {
			return;
		}

		$this->includes();

		add_action( 'wp_enqueue_scripts',    array( $this, 'scripts' ) );
		add_action( 'rcp_view_member_after', array( $this, 'member_details' ) );
	}

	protected function includes() {
		Admin\Init::get_instance();

		$this->handle_taxes  = HandleTaxes::get_instance();
		$this->member_fields = MemberFields::get_instance();
		$this->logging       = Logging::get_instance();

		Gateways\Stripe::get_instance();
	}

	/**
	 * Make sure RCP is active
	 * @return bool
	 */
	protected function check_required_plugins() {

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		if ( is_plugin_active( 'restrict-content-pro/restrict-content-pro.php' ) ) {
			return true;
		}

		add_action( 'admin_notices', array( $this, 'required_plugins' ) );

		return false;
	}

	/**
	 * Required Plugins notice
	 */
	public function required_plugins() {
		printf( '<div class="error"><p>%s</p></div>', __( 'Restrict Content Pro is required for the Restrict Content Pro - AvaTax add-on to function.', 'rcp-avatax' ) );
	}

	public function scripts() {}

	/**
	 * Render the country field for member details.
	 */
	public function member_details( $user_id ) {
		$country   = get_user_meta( $user_id, 'rcp_country', true );
		$countries = self::get_countries();
		if ( empty( $country ) ) {
			return;
		} ?>
		<tr class="form-field">
		<th scope="row" valign="top">
			<?php _e( 'Country', 'rcp-avatax' ); ?>
		</th>
		<td>
			<?php echo $countries[ $country ]; ?>
		</td>
		</tr><?php
	}

	/** Helper Methods **************************************/

	/**
	 * @param $key
	 * @param $default
	 *
	 * @return string
	 */
	public static function get_settings( $key = false, $default = '' ) {
		$settings = get_option( 'rcp_avatax', '' );

		if ( ! $key ) {
			return $settings;
		}

		if ( empty( $settings[ $key ] ) ) {
			$settings[ $key ] = $default;
		}

		return apply_filters( 'rcp_avatax_get_setting', $settings[ $key ], $key, $default );
	}

	/**
	 * Gets the plugin documentation URL
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_documentation_url() {
		return '';
	}

	/**
	 * Gets the plugin support URL
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_support_url() {

		return '';
	}

	/**
	 * Returns the plugin name, localized
	 *
	 * @since 1.0.0
	 * @return string the plugin name
	 */
	public function get_plugin_name() {
		return __( 'RCP AvaTax', 'rcp-avatax' );
	}

	public function get_id() {
		return 'rcp-avatax';
	}

	/**
	 * Returns __FILE__
	 *
	 * @since 1.0.0
	 * @return string the full path and filename of the plugin file
	 */
	protected function get_file() {
		return RCP_AVATAX_PLUGIN_FILE;
	}

	/**
	 * Returns the current version of the plugin
	 *
	 * @since 1.0.0
	 * @return string plugin version
	 */
	public function get_version() {
		return RCP_AVATAX_PLUGIN_VERSION;
	}

	/** Subscription Meta Helpers ********************/

	/**
	 * Save subscription meta
	 *
	 * @param $subscription_id
	 * @param $values
	 */
	public static function meta_save( $subscription_id, $values ) {
		global $rcp_levels_db;

		$values = apply_filters( 'rcp_avatax_tax_code_save_sanitize', $values );

		$rcp_levels_db->update_meta( $subscription_id, 'avatax_meta', $values );
	}

	/**
	 * Get subscription meta
	 *
	 * @param      $subscription_id
	 * @param null $key
	 *
	 * @return mixed
	 */
	public static function meta_get( $subscription_id, $key = null ) {
		global $rcp_levels_db;

		if ( ! $meta = $rcp_levels_db->get_meta( $subscription_id, 'avatax_meta', true ) ) {
			$meta = get_option( Levels::get_option_key(), array() );
			$meta = Helpers::get_param( $meta, $subscription_id, array() );
			if ( $key ) {
				$key = 'avatax-' . $key;
			}
		}

		if ( $key ) {
			$meta = Helpers::get_param( $meta, $key );
		}

		return apply_filters( 'rcp_avatax_tax_code_get', $meta, $key, $subscription_id );
	}

	/** API Helpers ********************/

	/**
	 * Return new API request
	 *
	 * @return API
	 */
	public static function new_request() {
		$account_number = self::get_settings( 'avatax_account_number' );
		$license_key    = self::get_settings( 'avatax_license_key' );

		return new API( $account_number, $license_key );
	}

}
<?php

namespace RCP_Avatax\Admin;

use RCP_Avatax\Init as RCP_Avatax;
use RCP_Avatax\AvaTax\API;
use RCP_Avatax\LoggingTable;

class Settings {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of \RCP_Avatax\Settings
	 *
	 * @return Settings
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Settings ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ), 50 );
		add_action( 'admin_menu', array( $this, 'admin_menu'        ), 50 );
	}

	/**
	 * Register the RCP BP settings
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function register_settings() {
		register_setting( 'rcp_avatax_settings_group', 'rcp_avatax', array( $this, 'sanitize_settings' ) );
	}

	/**
	 * Add the AvaTax menu item
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function admin_menu() {
		add_submenu_page( 'rcp-members', __( 'AvaTax Settings', 'rcp-avatax' ), __( 'AvaTax', 'rcp-avatax' ), 'manage_options', 'rcp-avatax-settings', array( $this, 'settings_page' ) );
		add_submenu_page( 'rcp-avatax-settings', __( 'AvaTax Settings', 'rcp-avatax' ), __( 'AvaTax Log', 'rcp-avatax' ), 'manage_options', 'rcp-avatax-log', array( $this, 'log_settings_page' ) );
	}

	/**
	 * AvaTax Settings page
	 */
	public function settings_page() {

		// enqueue RCP js for tabs
		wp_enqueue_script( 'bbq',  RCP_PLUGIN_URL . 'includes/js/jquery.ba-bbq.min.js' );
		wp_enqueue_script( 'rcp-admin-scripts',  RCP_PLUGIN_URL . 'includes/js/admin-scripts.js', array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker', 'jquery-ui-tooltip' ), RCP_PLUGIN_VERSION );

		$status   = get_option( 'rcp_avatax_license_status', '' );

		if ( isset( $_REQUEST['updated'] ) && $_REQUEST['updated'] !== false ) : ?>
			<div class="updated fade"><p><strong><?php _e( 'Options saved', 'rcp-avatax' ); ?></strong></p></div>
		<?php endif; ?>

		<?php settings_errors(); ?>

		<div class="wrap">

			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form method="post" action="options.php" class="rcp_options_form">
				<?php settings_fields( 'rcp_avatax_settings_group' ); ?>

				<h2 class="nav-tab-wrapper">
					<a href="#general" class="nav-tab"><?php _e( 'General', 'rcp-avatax' ); ?></a>
					<a href="<?php admin_url(); ?>?page=rcp-avatax-log" class="nav-tab"><?php _e( 'Log', 'rcp-avatax' ); ?></a>
					<a href="#license" class="nav-tab"><?php _e( 'License', 'rcp-avatax' ); ?></a>
				</h2>

				<div id="tab_container">
					<div class="tab_content" id="general">
						<table class="form-table">
							<tr valign="top">
								<th colspan=2><h3><?php _e( 'AvaTax Account Info', 'rcp-avatax' ); ?></h3></th>
							</tr>
							<tr valign="top">
								<th>
									<label for="rcp_avatax[avatax_account_number]"><?php _e( 'Account Number', 'rcp-avatax' ); ?></label>
								</th>
								<td>
									<input type="text" class="regular-text" id="rcp_avatax[avatax_account_number]" style="width: 300px;" name="rcp_avatax[avatax_account_number]" value="<?php echo esc_attr( RCP_Avatax::get_settings( 'avatax_account_number' ) ); ?>" />

									<p class="description"><?php _e( 'Enter your Avalara Account Number.', 'rcp-avatax' ); ?></p>
								</td>
							</tr>
							<tr valign="top">
								<th>
									<label for="rcp_avatax[avatax_license_key]"><?php _e( 'License Key', 'rcp-avatax' ); ?></label>
								</th>
								<td>
									<input type="text" class="regular-text" id="rcp_avatax[avatax_license_key]" style="width: 300px;" name="rcp_avatax[avatax_license_key]" value="<?php echo esc_attr( RCP_Avatax::get_settings( 'avatax_license_key' ) ); ?>" />

									<p class="description"><?php _e( 'Enter your Avalara License Key.', 'rcp-avatax' ); ?></p>
								</td>
							</tr>
							<tr valign="top">
								<th>
									<label for="rcp_avatax[avatax_company_code]"><?php _e( 'Company Code', 'rcp-avatax' ); ?></label>
								</th>
								<td>
									<input type="text" class="regular-text" id="rcp_avatax[avatax_company_code]" style="width: 300px;" name="rcp_avatax[avatax_company_code]" value="<?php echo esc_attr( RCP_Avatax::get_settings( 'avatax_company_code' ) ); ?>" />

									<p class="description"><?php _e( 'Enter the Avalara Company Code to use.', 'rcp-avatax' ); ?></p>
								</td>
							</tr>
							<tr valign="top">
								<th>
									<label for="rcp_avatax[sandbox_mode]"><?php _e( 'Sandbox Mode', 'rcp-avatax' ); ?></label>
								</th>
								<td>
									<input type="checkbox" id="rcp_avatax[sandbox_mode]" name="rcp_avatax[sandbox_mode]" <?php checked( RCP_Avatax::get_settings( 'sandbox_mode' ) ); ?> />
									<span class="description"><?php _e( 'Use RCP - AvaTax in Sandbox mode.', 'rcp-avatax' ); ?></span>
								</td>
							</tr>
							<tr valign="top">
								<th>
									<label for="rcp_avatax[show_vat_field]"><?php _e( 'Show EU VAT field', 'rcp-avatax' ); ?></label>
								</th>
								<td>
									<input type="checkbox" id="rcp_avatax[show_vat_field]" name="rcp_avatax[show_vat_field]" <?php checked( RCP_Avatax::get_settings( 'show_vat_field' ) ); ?> />
									<span class="description"><?php _e( 'Show the EU VAT field during registration.', 'rcp-avatax' ); ?></span>
								</td>
							</tr>
							<tr valign="top">
								<th>
									<label for="rcp_avatax[disable_commit]"><?php _e( 'Disable Document Commit', 'rcp-avatax' ); ?></label>
								</th>
								<td>
									<input type="checkbox" id="rcp_avatax[disable_commit]" name="rcp_avatax[disable_commit]" <?php checked( RCP_Avatax::get_settings( 'disable_commit' ) ); ?> />
									<span class="description"><?php _e( 'Causes the document to not be committed to AvaTax.', 'rcp-avatax' ); ?></span>
								</td>
							</tr>
							<tr valign="top">
								<th>
									<label for="rcp_avatax[disable_calculation]"><?php _e( 'Disable Checkout Calculation', 'rcp-avatax' ); ?></label>
								</th>
								<td>
									<input type="checkbox" id="rcp_avatax[disable_calculation]" name="rcp_avatax[disable_calculation]" <?php checked( RCP_Avatax::get_settings( 'disable_calculation' ) ); ?> />
									<span class="description"><?php _e( 'Disable calculation of taxes on the registration page. Taxes will still be calculated for payments.', 'rcp-avatax' ); ?></span>
								</td>
							</tr>
							<tr valign="top">
								<th>
									<label for="rcp_avatax[test_connection]"><?php _e( 'Test AvaTax Connection', 'rcp-avatax' ); ?></label>
								</th>
								<td>
									<input type="checkbox" id="rcp_avatax[test_connection]" name="rcp_avatax[test_connection]" />
									<span class="description"><?php _e( 'Test the connection to AvaTax.', 'rcp-avatax' ); ?></span>
								</td>
							</tr>
						</table>
					</div>

					<div class="tab_content" id="license">
						<table class="form-table">
							<tr>
								<th>
									<label for="rcp_avatax[license_key]"><?php _e( 'RCP - AvaTax License Key', 'rcp-avatax' ); ?></label>
								</th>
								<td>
									<p>
										<input class="regular-text" type="text" id="rcp_avatax[license_key]" name="rcp_avatax[license_key]" value="<?php echo esc_attr( RCP_Avatax::get_settings( 'license_key' ) ); ?>" />
										<?php if ( $status == 'valid' ) : ?>
											<?php wp_nonce_field( 'rcp_avatax_deactivate_license', 'rcp_avatax_deactivate_license' ); ?>
											<?php submit_button( 'Deactivate License', 'secondary', 'rcp_avatax_license_deactivate', false ); ?>
											<span style="color:green">&nbsp;&nbsp;<?php _e( 'active', 'rcp-avatax' ); ?></span>
										<?php else : ?>
											<?php submit_button( 'Activate License', 'secondary', 'rcp_avatax_license_activate', false ); ?>
										<?php endif; ?></p>

									<p class="description"><?php printf( __( 'Enter your Restrict Content Pro - AvaTax license key. This is required for automatic updates and <a href="%s">support</a>.', 'rcp-avatax' ), 'https://skilledcode.com/support' ); ?></p>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<?php settings_fields( 'rcp_avatax_settings_group' ); ?>
				<?php wp_nonce_field( 'rcp_avatax_nonce', 'rcp_avatax_nonce' ); ?>
				<?php submit_button( __( 'Save Settings', 'rcp-avatax' ) ); ?>

			</form>
		</div>
	<?php
	}

	/**
	 * Use separate page for the log
	 */
	public function log_settings_page() {
		?>
		<div class="wrap">

			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form method="get" action="" class="rcp_options_form">

				<input type="hidden" name="page" value="rcp-avatax-log" />

				<h2 class="nav-tab-wrapper">
					<a href="<?php admin_url(); ?>?page=rcp-avatax-settings#general" class="nav-tab"><?php _e( 'General', 'rcp-avatax' ); ?></a>
					<a href="#" class="nav-tab nav-tab-active"><?php _e( 'Log', 'rcp-avatax' ); ?></a>
					<a href="<?php admin_url(); ?>?page=rcp-avatax-settings#license" class="nav-tab"><?php _e( 'License', 'rcp-avatax' ); ?></a>
				</h2>

				<div id="tab_container">

					<div class="tab_content" id="log">
						<?php if ( isset( $_GET['view-log'] ) ) : ?>
							<p><a href="<?php echo remove_query_arg( 'view-log' ); ?>"><?php _e( '&larr; Back to the log', 'rcp-avatax' ); ?></a></p>

							<?php
							if ( ! $item = get_post( $_GET['view-log'] ) ) {
								printf( '<p>%s</p>', __( 'That log item could not be found', 'rcp-avatax' ) );
							} else {
								$date = strtotime( get_post_field( 'post_date', $item->ID ) );
								$date = date_i18n( get_option( 'date_format' ), $date ) . ' ' . __( 'at', 'rcp-avatax' ) . ' ' . date_i18n( get_option( 'time_format' ), $date );
								printf( '<h2>(%s) %s</h2>', $item->ID, $date );

								printf( '<h3>%s</h3>', __( 'Request Data', 'rcp-avatax' ) );
								$request = maybe_unserialize( get_post_meta( $item->ID, '_log_request_data', true ) );
								var_dump( $request );

								printf( '<h3>%s</h3>', __( 'Response Data', 'rcp-avatax' ) );
								$response = unserialize( trim( get_post_meta( $item->ID, '_log_response_data', true ) ) );
								var_dump( $response );
							}
							?>
						<?php else : ?>
							<?php
							$logs_table = new LoggingTable();
							$logs_table->prepare_items();
							$logs_table->views();
							$logs_table->display();
							?>
						<?php endif; ?>
					</div>

				</div>

			</form>
		</div>

		<?php

	}

	/**
	 * Sanitize AvaTax settings
	 *
	 * @param $new
	 *
	 * @return mixed
	 */
	public function sanitize_settings( $new ) {
		$old_license = RCP_Avatax::get_settings( 'license_key' );
		$new_license = empty( $new['license_key'] ) ? '' : $new['license_key'];

		if ( $old_license && $old_license != $new_license ) {
			delete_option( 'rcp_avatax_license_status' ); // new license has been entered, so must reactivate
		}

		$new['show_vat_field']      = isset( $new['show_vat_field'] ) ? true : false;
		$new['sandbox_mode']        = isset( $new['sandbox_mode'] ) ? true : false;
		$new['disable_commit']      = isset( $new['disable_commit'] ) ? true : false;
		$new['disable_calculation'] = isset( $new['disable_calculation'] ) ? true : false;

		if ( ! empty( $new['test_connection'] ) && ! empty( $new['avatax_account_number'] ) && ! empty( $new['avatax_license_key'] ) && ! empty( $new['avatax_company_code'] ) ) {
			$request = new API( $new['avatax_account_number'], $new['avatax_license_key'] );
			$response = $request->test( $new['avatax_company_code'] );

			if ( $response->response_data->authenticated ) {
				add_settings_error( 'general', 'avatax_connection', __( 'Connection to AvaTax was successful.', 'rcp-avatax' ), 'updated' );
			} else {
				add_settings_error( 'general', 'avatax_connection', __( 'There was an error connecting to AvaTax. Please verify your credentials.', 'rcp-avatax' ), 'error' );
			}
		}

		return array_map( 'sanitize_text_field', $new );
	}

}
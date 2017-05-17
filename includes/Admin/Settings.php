<?php

namespace RCP_Avatax\Admin;

use RCP_Avatax\Init as RCP_Avatax;

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
	}

	public function settings_page() {
		$status   = get_option( 'rcp_avatax_license_status', '' );

		if ( isset( $_REQUEST['updated'] ) && $_REQUEST['updated'] !== false ) : ?>
			<div class="updated fade"><p><strong><?php _e( 'Options saved', 'rcp-avatax' ); ?></strong></p></div>
		<?php endif; ?>

		<div class="rcp-avatax-wrap">

			<h2 class="rcp-avatax-settings-title"><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<hr>

			<form method="post" action="options.php" class="rcp_options_form">
				<?php settings_fields( 'rcp_avatax_settings_group' ); ?>

				<table class="form-table">
					<tr>
						<th>
							<label for="rcp_avatax[license_key]"><?php _e( 'License Key', 'rcp-avatax' ); ?></label>
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

				<hr />

				<table class="form-table">
					<tr valign="top">
						<th colspan=2><h3><?php _e( 'Account Info', 'rcp-avatax' ); ?></h3></th>
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
							<label for="rcp_avatax[show_vat_field]"><?php _e( 'Show EU VAT field', 'rcp-avatax' ); ?></label>
						</th>
						<td>
							<input type="checkbox" id="rcp_avatax[show_vat_field]" name="rcp_avatax[show_vat_field]" <?php checked( RCP_Avatax::get_settings( 'show_vat_field' ) ); ?> />

							<p class="description"><?php _e( 'Show the EU VAT field during registration.', 'rcp-avatax' ); ?></p>
						</td>
					</tr>
				</table>

				<?php settings_fields( 'rcp_avatax_settings_group' ); ?>
				<?php wp_nonce_field( 'rcp_avatax_nonce', 'rcp_avatax_nonce' ); ?>
				<?php submit_button( 'Save Options' ); ?>

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
		$new_licence = empty( $new['license_key'] ) ? '' : $new['license_key'];

		if ( $old_license && $old_license != $new_licence ) {
			delete_option( 'rcp_avatax_license_status' ); // new license has been entered, so must reactivate
		}

		$new['show_vat_field'] = isset( $new['show_vat_field'] ) ? true : false;

		return array_map( 'sanitize_text_field', $new );
	}

}
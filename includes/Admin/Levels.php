<?php

namespace RCP_Avatax\Admin;

use RCP_Avatax\Init as RCP_Avatax;

class Levels {

	/**
	 * @var
	 */
	protected static $_instance;

	protected static $_option_key = 'rcp_avatax_tax_codes';

	/**
	 * Only make one instance of \RCP_Avatax\Levels
	 *
	 * @return Levels
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Levels ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		$this->hooks();
	}

	/**
	 * Actions and Filters
	 */
	protected function hooks() {

		// Add form field to subscription level add and edit forms
		add_action( 'rcp_add_subscription_form',  array( $this, 'meta_fields' ) );
		add_action( 'rcp_edit_subscription_form', array( $this, 'meta_fields' ) );

		// Actions for saving subscription seat count
		add_action( 'rcp_edit_subscription_level', array( $this, 'handle_meta_save' ), 10, 2 );
		add_action( 'rcp_add_subscription',        array( $this, 'handle_meta_save' ), 10, 2 );

		add_filter( 'rcp_avatax_tax_code_save_sanitize', array( $this, 'sanitize_avatax_item' ) );

	}

	/**
	 * Add meta fields to the RCP levels
	 * @param null $level
	 */
	public function meta_fields( $level = null ) {
		$item_name = ( empty( $level->id ) ) ? 0 : RCP_Avatax::meta_get( $level->id, 'item' );
		$tax_code  = ( empty( $level->id ) ) ? 0 : RCP_Avatax::meta_get( $level->id, 'taxcode' ); ?>

		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="rcp-avatax[item]"><?php _e( 'AvaTax Item', 'rcp-avatax' ); ?></label>
			</th>
			<td>
				<input id="rcp-avatax[item]" type="text" name="rcp-avatax[item]" value="<?php echo esc_attr( $item_name ); ?>" style="width: 20em;"/>
				<p class="description"><?php _e( 'The AvaTax Item associated with this subscription level.', 'rcp-avatax' ); ?></p>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="rcp-avatax[taxcode]"><?php _e( 'AvaTax TaxCode', 'rcp-avatax' ); ?></label>
			</th>
			<td>
				<input id="rcp-avatax[taxcode]" type="text" name="rcp-avatax[taxcode]" value="<?php echo esc_attr( $tax_code ); ?>" style="width: 20em;"/>
				<p class="description"><?php _e( 'The AvaTax Item associated with this subscription level.', 'rcp-avatax' ); ?></p>
			</td>
		</tr>

		<?php do_action( 'rcp_avatax_level_settings', $level ); ?>

	<?php
	}

	/**
	 * Save the member type for this subscription
	 *
	 * @param $subscription_id
	 * @param $args
	 */
	public function handle_meta_save( $subscription_id, $args ) {
		// make sure the member type is set
		if ( empty( $_POST['rcp-avatax'] ) ) {
			return;
		}

		RCP_Avatax::meta_save( $subscription_id, $_POST['rcp-avatax'] );
	}

	/**
	 * Sanitize the AvaTax Item field
	 *
	 * @param $values
	 *
	 * @return mixed
	 */
	public function sanitize_avatax_item( $values ) {

		$default = array(
			'item' => '',
			'taxcode' => '',
		);

		wp_parse_args( $values, $default );

		$values['item']    = sanitize_text_field( $values['item'] );
		$values['taxcode'] = sanitize_text_field( $values['taxcode'] );

		return $values;

	}

	/**
	 * @return string
	 */
	public static function get_option_key() {
		return self::$_option_key;
	}

}
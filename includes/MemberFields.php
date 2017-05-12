<?php

namespace RCP_Avatax;

use Iso3166\Codes;

class MemberFields {

	/**
	 * @var
	 */
	protected static $_instance;

	public $address_fields = array(
		'rcp_card_address',
		'rcp_card_address_2',
		'rcp_card_city',
		'rcp_card_state',
		'rcp_card_zip',
		'rcp_card_country',
		'rcp_vat_id',
	);

	/**
	 * Only make one instance of \RCP_Avatax\MemberFields
	 *
	 * @return MemberFields
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof MemberFields ) {
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


	public function hooks() {

		// Add address fields to User Forms.
		add_action( 'rcp_before_subscription_form_fields', array( $this, 'address_fields' ) );
		add_action( 'rcp_profile_editor_after',            array( $this, 'address_fields' ) );

		// Process User Forms, Update User address
		add_action( 'rcp_user_profile_updated', array( $this, 'save_user_address'              ), 10, 2 );
		add_action( 'rcp_form_processing',      array( $this, 'save_user_address_registration' ), 10, 2 );

		add_filter( 'rcp_get_template_part', array( $this, 'short_card_form' ), 10, 3 );
	}

	/**
	 * Map the fields to $this->save_user_address.
	 *
	 * @param $post
	 * @param $user_id
	 */
	public function save_user_address_registration( $post, $user_id ) {
		$this->save_user_address( $user_id );
	}

	/**
	 * Save user address
	 *
	 * @param      $user_id
	 * @param null $userdata
	 */
	public function save_user_address( $user_id, $userdata = null ) {

		foreach( $this->address_fields as $field ) {
			if ( ! isset( $_POST[ $field ] ) ) {
				continue;
			}

			update_user_meta( $user_id, $field, sanitize_text_field( $_POST[ $field ] ) );
		}

	}

	/**
	 * Return the user's stored address
	 *
	 * @param $user_id
	 *
	 * @return array
	 */
	public function get_user_address( $user_id ) {
		$address = array();

		foreach( $this->address_fields as $field ) {
			$address[ $field ] = get_user_meta( $user_id, $field, true );
		}

		return $address;
	}

	/**
	 * Don't use the full card form when we are printing out the address fields separate
	 *
	 * @param $templates
	 * @param $slug
	 * @param $name
	 *
	 * @return mixed
	 */
	public function short_card_form( $templates, $slug, $name ) {

		if ( 'card-form-full' !== $slug ) {
			return $templates;
		}

		$key = array_search( 'card-form-full.php', $templates );

		if ( false === $key ) {
			return $templates;
		}

		$templates[ $key ] = 'card-form.php';

		return $templates;

	}


	/**
	 * Print out address fields
	 *
	 * @param null $user_id
	 */
	public function address_fields( $user_id = NULL ) {

		if( !$user_id ) {
			$user_id = get_current_user_id();
		}

		$countries       = apply_filters( 'rcp_avatax_country_list', Codes::$countries );
		$default_country = $this->get_field( 'rcp_card_country', $user_id, apply_filters( 'rcp_avatax_country_default', 'US' ) ); ?>

		<?php if ( apply_filters( 'rcp_avatax_form_styling_show', true ) ) : ?>
			<style>
				@media screen and (min-width:728px) {
					#rcp_card_state_wrap {
						width: 29%;
						float: left;
					}

					#rcp_card_state {
						width: 3em;
					}

					#rcp_card_country_wrap  {
						width: 69%;
						float: right;
					}
				}
			</style>
		<?php endif; ?>

		<fieldset class="rcp_avatax_fieldset">

			<legend><?php echo apply_filters( 'rcp_avatax_address_title', __( 'Billing Address', 'rcp-avatax' ) ); ?></legend>

			<?php if ( apply_filters( 'rcp_avatax_show_vat', rcp_avatax()::get_settings( 'show_vat', false ) ) ): ?>
				<p id="rcp_vat_id_wrap">
					<label for="rcp_vat_id"><?php _e( 'VAT ID', 'rcptx' ); ?></label>
					<input name="rcp_vat_id" id="rcp_vat_id" type="text" value="<?php echo $this->get_field( 'rcp_vat_id', $user_id ); ?>" />
				</p>
			<?php endif; ?>

			<p id="rcp_card_address_wrap">
				<label for="rcp_card_address"><?php echo apply_filters ( 'rcp_card_address_label', __( 'Address Line 1', 'rcp-avatax' ) ); ?></label>
				<input name="rcp_card_address" id="rcp_card_address" class="required rcp_card_address card-address" type="text" value="<?php echo esc_attr( $this->get_field( 'rcp_card_address', $user_id ) ); ?>" />
			</p>
			<p id="rcp_card_address_2_wrap">
				<label for="rcp_card_address_2"><?php echo apply_filters ( 'rcp_card_address_2_label', __( 'Address Line 2', 'rcp-avatax' ) ); ?></label>
				<input name="rcp_card_address_2" id="rcp_card_address_2" class="rcp_card_address_2 card-address-2" type="text" value="<?php echo esc_attr( $this->get_field( 'rcp_card_address_2', $user_id ) ); ?>" />
			</p>
			<p id="rcp_card_city_wrap">
				<label for="rcp_card_city"><?php echo apply_filters ( 'rcp_card_city_label', __( 'City', 'rcp-avatax' ) ); ?></label>
				<input name="rcp_card_city" id="rcp_card_city" class="required rcp_card_city card-city" type="text" value="<?php echo esc_attr( $this->get_field( 'rcp_card_city', $user_id ) ); ?>" />
			</p>
			<p id="rcp_card_state_wrap">
				<label for="rcp_card_state"><?php echo apply_filters ( 'rcp_card_state_label', __( 'State or Providence', 'rcp-avatax' ) ); ?></label>
				<input name="rcp_card_state" id="rcp_card_state" class="required rcp_card_state card-state" type="text" maxlength="3" value="<?php echo esc_attr( $this->get_field( 'rcp_card_state', $user_id ) ); ?>" />
			</p>
			<p id="rcp_card_country_wrap">
				<label for="rcp_card_country"><?php echo apply_filters ( 'rcp_card_country_label', __( 'Country', 'rcp-avatax' ) ); ?></label>
				<select id="rcp_card_country" name="rcp_card_country">
					<?php foreach( $countries as $code => $country ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $default_country, $code ); ?>><?php echo esc_html( $country ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>

		</fieldset><?php

	}

	/**
	 * Get user meta field. Prioritize $_POST value before user_meta
	 *
	 * @param      $field
	 * @param null $user_id
	 *
	 * @return mixed|string
	 */
	public function get_field( $field, $user_id = null, $default = '' ) {

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( ! $meta = get_user_meta( $user_id, $field, true ) ) {
			$meta = $default;
		}

		if ( isset( $_POST[ $field ] ) ) {
			$meta = $_POST[ $field ];
		}

		return apply_filters( 'rcp_avatax_get_member_field', $meta, $field, $user_id, $default );

	}

}
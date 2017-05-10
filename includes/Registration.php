<?php

namespace RCP_Avatax;

class Registration {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * @var null
	 */
	public $total = null;

	/**
	 * @var null
	 */
	public $total_recurring = null;

	/**
	 * @var null
	 */
	public $total_tax = null;

	/**
	 * @var null
	 */
	public $total_recurring_tax = null;

	/**
	 * Only make one instance of \RCP_Avatax\Registration
	 *
	 * @return Registration
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Registration ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function __construct() {
		add_action( 'rcp_setup_registration', array( $this, 'calculate_tax' ), 500 );
//		add_action( 'rcp_setup_registration', array( $this, 'gateway_include' ) );
	}

	/**
	 * Calculate tax for this registration
	 */
	public function calculate_tax() {

		$this->reset_vars();

		if ( empty( $_POST['rcp_card_address'] ) ) {
			return;
		}

		$request = rcp_avatax()::calculate_registration_tax();

		if ( $request instanceof \SkilledCode\Exception ) {
			return;
		}

		if ( ! isset( $request->response_data->lines[0]->tax, $request->response_data->lines[1]->tax ) ) {
			return;
		}

		$this->total               = rcp_get_registration_total();
		$this->total_recurring     = rcp_get_registration_recurring_total();
		$this->total_tax           = $request->response_data->lines[0]->tax;
		$this->total_recurring_tax = $request->response_data->lines[1]->tax;

		if ( 0 < $this->total_tax ) {
			rcp_get_registration()->add_fee( $this->total_tax, __( 'Tax Today', 'rcp-avatax' ), false );
		}

		if ( 0 < $this->total_recurring_tax ) {
			rcp_get_registration()->add_fee( $this->total_recurring_tax, __( 'Tax Recurring', 'rcp-avatax' ), true );
		}

	}

	/**
	 * Reset class vars
	 */
	public function reset_vars() {
		$this->total = $this->total_recurring = $this->total_tax = $this->total_recurring_tax = null;
	}

	/**
	 * Include gateway to handle custom processing
	 */
	public function gateway_include() {

		// get the selected payment method/gateway
		if ( ! isset( $_POST['rcp_gateway'] ) ) {
			$gateway = 'paypal';
		} else {
			$gateway = sanitize_text_field( $_POST['rcp_gateway'] );
		}

		if ( apply_filters( 'rcp_avatax_gateway_included', false, $gateway ) ) {
			return;
		}

		switch( $gateway ) {
			case 'stripe' :
				Gateways\Stripe::get_instance();
				return;
		}

	}

}
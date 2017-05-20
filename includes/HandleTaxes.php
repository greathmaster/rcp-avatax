<?php

namespace RCP_Avatax;

use SkilledCode\Helpers;
use SkilledCode\RequestAPI\Exception;
use RCP_Avatax\Init as RCP_Avatax;

class HandleTaxes {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * @var null
	 */
	public $rate = null;

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
	 * @var string
	 */
	public $tax_id = '';

	/**
	 * @var string
	 */
	public $recurring_tax_id = '';

	/**
	 * Only make one instance of HandleTaxes
	 *
	 * @return HandleTaxes
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof HandleTaxes ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function __construct() {
		add_action( 'rcp_setup_registration', array( $this, 'calculate_tax'    ), 500 );
		add_action( 'rcp_insert_payment',     array( $this, 'maybe_record_tax' ), 10, 2 );
		add_action( 'rcp_form_errors',        array( $this, 'validate_address' ) );

		add_filter( 'rcp_subscription_data',  array( $this, 'add_subscription_tax' ) );
	}

	/**
	 * Reset class vars
	 */
	public function reset_vars() {
		$this->total = $this->total_recurring = $this->total_tax = $this->total_recurring_tax = null;
	}

	/**
	 * Calculate tax for this registration
	 *
	 * @param bool $registering | false if this is calculating
	 */
	public function calculate_tax( $registering = false ) {

		// make sure calculations are not disabled
		if ( ! $registering && RCP_Avatax::get_settings( 'disable_calculation', false ) ) {
			return;
		}

		try {

			$this->reset_vars();

			if ( empty( $_POST['rcp_card_address'] ) ) {
				return;
			}

			$request = RCP_Avatax::new_request();
			$response = $request->calculate_registration_tax();

			if ( ! isset( $response->response_data->lines[0]->tax, $response->response_data->lines[1]->tax ) ) {
				return;
			}

			$this->rate                = $response->get_detail( 'taxRate' );
			$this->total               = rcp_get_registration_total();
			$this->total_recurring     = rcp_get_registration_recurring_total();
			$this->total_tax           = $response->response_data->lines[0]->tax;
			$this->total_recurring_tax = $response->response_data->lines[1]->tax;

			// Don't add fees if we really are registering. We'll add those later.
			if ( isset( $_POST['rcp_register_nonce'] ) ) {
				return;
			}

			$tax_args = array(
				'amount'      => '',
				'description' => '',
				'recurring'   => false,
				'proration'   => false,
			);

			if ( 0 < $this->total_tax ) {
				$tax_args['amount']      = floatval( $this->total_tax );
				$tax_args['description'] = apply_filters( 'rcp_avatax_tax_today_description',
					__( 'Tax Today', 'rcp-avatax' ), $this );

				if ( call_user_func_array( array( rcp_get_registration(), 'add_fee' ), $tax_args ) ) {
					$this->tax_id = md5( serialize( $tax_args ) );
				}

			}

			if ( 0 < $this->total_recurring_tax && rcp_registration_is_recurring() ) {
				$tax_args['amount']      = floatval( $this->total_recurring_tax );
				$tax_args['description'] = apply_filters( 'rcp_avatax_tax_recurring_description',
					__( 'Tax Recurring', 'rcp-avatax' ), $this );
				$tax_args['recurring']   = true;

				if ( call_user_func_array( array( rcp_get_registration(), 'add_fee' ), $tax_args ) ) {
					$this->recurring_tax_id = md5( serialize( $tax_args ) );
				}

			}

		} catch ( Exception $e ) {
			rcp_errors()->add( $e->getMessage(), $e->getMessage(), 'register' );
		}

	}

	/**
	 * Record transaction in AvaTax
	 *
	 * @param $payment_id
	 * @param $args
	 */
	public function maybe_record_tax( $payment_id, $args ) {

		$payments = new \RCP_Payments();

		try {

			$args['payment_id'] = $payment_id;

			$request  = RCP_Avatax::new_request();
			$response = $request->process_payment( $args );

			$details = $response->get_details();

			if ( empty( $details ) ) {
				throw new Exception( 'No tax details were returned for this payment.' );
			}

			$payments->add_meta( $payment_id, 'tax_details', $details );

		} catch ( Exception $e ) {
			$payments->add_meta( $payment_id, 'tax_request', sanitize_text_field( $e->getMessage() ) );
		}

	}

	/**
	 * Add rcp_error if Address is invalid
	 *
	 * @param $_post
	 */
	public function validate_address( $_post ) {

		try {

			$request = RCP_Avatax::new_request();
			$response = $request->validate_address( $_post );

			foreach( $response->get_validation_messages() as $key => $message ) {
				rcp_errors()->add( $key, $message, 'register' );
			}

		} catch( Exception $e ) {
			rcp_errors()->add( 'invalid-address', $e->getMessage(), 'register' );
		}

	}

	/**
	 * Add the tax to the subscription data
	 *
	 * @param $subscription_data
	 *
	 * @return mixed
	 */
	public function add_subscription_tax( $subscription_data ) {

		// make sure the taxes have been calculated
		if ( null === $this->rate ) {
			$this->calculate_tax( true );
		}

		if ( empty( $this->rate ) ) {
			return $subscription_data;
		}

		if ( apply_filters( 'rcp_avatax_subscription_data_add_price_tax', true, $subscription_data, $this ) ) {
			$subscription_data['price'] = $this->add_tax( $subscription_data['price'], $subscription_data['fee'] );
		}

		if ( apply_filters( 'rcp_avatax_subscription_data_add_recurring_price_tax', true, $subscription_data, $this ) ) {
			$subscription_data['recurring_price'] = $this->add_tax( $subscription_data['recurring_price'] );
		}

		return $subscription_data;

	}

	public function add_tax( $price, $fee = 0 ) {

		if ( 0 >= $price ) {
			return $price;
		}

		$tax = ( $price + $fee ) * $this->rate;

		if ( 0 > $tax ) {
			$tax = 0;
		}

		return apply_filters( 'rcp_avatax_handle_tax_add_tax', $price + $tax, $price );

	}

}
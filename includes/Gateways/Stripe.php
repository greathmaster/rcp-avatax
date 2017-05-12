<?php

namespace RCP_Avatax\Gateways;

class Stripe {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of \RCP_Avatax\Gateways\Stripe
	 *
	 * @return Stripe
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Stripe ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function __construct() {
		add_filter( 'rcp_stripe_create_subscription_args', array( $this, 'add_tax_to_charge' ), 10, 2 );
		add_action( 'rcp_stripe_charge_succeeded', array( $this, 'record_tax' ), 10, 3 );
	}

	public function add_tax_to_charge( $sub_args, $stripe_gateway ) {

		if ( rcp_avatax()->handle_taxes->rate ) {
			$sub_args['tax_percent'] = 100 * rcp_avatax()->handle_taxes->rate;
		}

		return $sub_args;
	}

	public function record_tax( $user, $payment_data, $event ) {

	}

}
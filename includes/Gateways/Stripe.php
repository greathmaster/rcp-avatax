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
		add_filter( 'rcp_stripe_create_subscription_args', array( $this, 'add_tax' ), 10, 2 );
	}

	public function add_tax( $sub_args, $stripe_gateway ) {
		rcp_avatax()->registration->total;
		return $sub_args;
	}

}
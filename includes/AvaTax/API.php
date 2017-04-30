<?php
/**
 * Define the AvaTax API class
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce AvaTax to newer
 * versions in the future. If you wish to customize WooCommerce AvaTax for your
 * needs please refer to http://docs.woocommerce.com/document/rcp-avatax/
 *
 * @package   AvaTax\API
 * @author    SkyVerge
 * @copyright Copyright (c) 2016-2017, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace RCP_Avatax\AvaTax;

use SkilledCode\RequestAPI\Base;
use SkilledCode\RequestAPI\Exception;

defined( 'ABSPATH' ) or exit;

/**
 * The AvaTax API.
 *
 * @since 1.0.0
 */
class API extends Base {

	/** @var  string base request URI */
	protected $request_uri;

	/** @var string response handler class */
	protected $response_handler;


	/**
	 * Construct the API.
	 *
	 * @since 1.0.0
	 * @param string $account_number The AvaTax account number.
	 * @param string $license_key The AvaTax license key.
	 * @param string $environment The current API environment, either `production` or `development`.
	 */
	public function __construct( $account_number, $license_key, $environment ) {

		$this->request_uri = ( 'production' === $environment ) ? 'https://rest.avatax.com/api/v2/' : 'https://sandbox-rest.avatax.com/api/v2/';

		$this->set_request_content_type_header( 'application/json' );
		$this->set_request_accept_header( 'application/json' );

		// Set basic auth creds
		$this->set_http_basic_auth( $account_number, $license_key );
	}

	/**
	 * Get the calculated tax for the current cart at checkout.
	 * @param null $post_data
	 *
	 * @return object
	 * @throws \Exception
	 * @throws \SkilledCode\Exception
	 */
	public function calculate_registration_tax( $post_data = null ) {

		$request = $this->get_new_request( 'tax' );

		try {

			// Process data
			$request->process_checkout( $post_data );

			// Perform request
			return $this->perform_request( $request );

		} catch ( \SkilledCode\Exception $e ) {

			return $e;

		}

	}

	/**
	 * Validate an address.
	 *
	 * @since 1.0.0
	 * @param array $address {
	 *     The address details.
	 *
	 *     @type string $address_1 Line 1 of the street address.
	 *     @type string $address_2 Line 2 of the street address.
	 *     @type string $city      The city name.
	 *     @type string $state     The state or region.
	 *     @type string $country   The country code.
	 *     @type string $postcode  The zip or postcode.
	 * }
	 * @return object The validated and normalized address.
	 */
	public function validate_address( $address ) {

		$request = $this->get_new_request( 'address' );

		$request->validate_address( $address );

		return $this->perform_request( $request );
	}


	/**
	 * Void a document in Avalara based on a WooCommerce order.
	 *
	 * @since 1.0.0
	 * @param int $order_id The associated order ID.
	 * @return \WC_AvaTax_API_Tax_Response
	 */
	public function void_order( $order_id ) {

		$request = $this->get_new_request();

		$request->void_order( $order_id );

		return $this->perform_request( $request );
	}


	/**
	 * Test the API credentials.
	 *
	 * This method pings the AvaTax API using the EstimateTax method as recommended
	 * in the AvaTax docs.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function test() {
		$request = $this->get_new_request();

		$request->test();

		return $this->perform_request( $request );
	}


	/**
	 * Allow child classes to validate a response prior to instantiating the
	 * response object. Useful for checking response codes or messages, e.g.
	 * throw an exception if the response code is not 200.
	 *
	 * A child class implementing this method should simply return true if the response
	 * processing should continue, or throw a \SkilledCode\RequestAPI\Exception with a
	 * relevant error message & code to stop processing.
	 *
	 * Note: Child classes *must* sanitize the raw response body before throwing
	 * an exception, as it will be included in the broadcast_request() method
	 * which is typically used to log requests.
	 *
	 * @since 1.0.0
	 */
	protected function do_pre_parse_response_validation() {

		// Get the response data
		$response      = $this->get_parsed_response( $this->get_raw_response_body() );
		$response      = $response->response_data;
		$response_code = $this->get_response_code();

		if ( ! is_object( $response ) && 200 !== $response_code ) {
			throw new Exception( __( 'Could not connect to AvaTax.', 'rcp-avatax' ), $response_code );
		}

		// For some reason the void endpoint returns a different object structure, so we need to check for that.
		if ( isset( $response->CancelTaxResult ) ) {
			$response = $response->CancelTaxResult;
		}

		if ( empty( $response->code ) ) {
			throw new Exception( $this->get_response_exception_message( $response ), $response_code );
		}

		return true;
	}


	/**
	 * Provide the log with more specific response exception messages for easier debugging.
	 *
	 * @since 1.0.0
	 * @param object $response The AvaTax API response.
	 * @return string
	 */
	protected function get_response_exception_message( $response ) {

		$default_message = 'Unspecified error.';

		if ( empty( $response->error ) ) {
			return $default_message;
		}

		$error = $response->error;

		foreach( $error->details as $detail ) {

			if ( empty( $detail->message ) ) {
				continue;
			}

			$default_message = $detail->message;

			switch( $detail->message ) {

				case 'The address is not deliverable.' :
					return $detail->message;

			}

		}

		return $default_message;

		$message = $response->error;

		switch ( $message->message ) {

			case 'Field customerCode is required.':
				$summary = __( 'Billing email address is missing.', 'rcp-avatax' );
			break;

			case 'An Address is incomplete or invalid.':

				if ( 'Addresses[0]' === $message->RefersTo ) {
					$summary = __( 'Invalid origin address. Please update your tax calculation settings.', 'rcp-avatax' );
				} else {
					$summary = __( 'Invalid destination address.', 'rcp-avatax' );
				}

			break;

			case 'Lines is expected to be between 1 and 15000.':
				$summary = __( 'The order has no line items.', 'rcp-avatax' );
			break;

			default:
				$summary = $message->Summary;
			break;
		}

		return $summary;
	}


	/**
	 * Builds and returns a new API request object
	 *
	 * @since 1.0.0
	 * @param string $type The desired request type
	 * @return Requests\Request|Requests\RequestAddress|Requests\RequestTax
	 */
	protected function get_new_request( $type = '' ) {

		switch ( $type ) {

			case 'tax':
				$this->set_response_handler( 'RCP_Avatax\AvaTax\Responses\ResponseTax' );
				return new Requests\RequestTax();
			break;

			case 'address':
				$this->set_response_handler( 'RCP_Avatax\AvaTax\Responses\ResponseAddress' );
				return new Requests\RequestAddress();
			break;

			default:
				$this->set_response_handler( 'RCP_Avatax\AvaTax\Responses\Response' );
				return new Requests\Request();
		}
	}


	/**
	 * Return the plugin class instance associated with this API.
	 *
	 * @since 1.0.0
	 * @return \RCP_Avatax\Init
	 */
	protected function get_plugin() {
		return rcp_avatax();
	}


}

<?php
/**
 * Define the RequestTax class
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace RCP_Avatax\Avatax\Requests;

use RCP_Avatax\AvaTax\Requests\Request;
use SkilledCode\Helpers;
use SkilledCode\RequestAPI\Exception;
use RCP_Avatax\Init as RCP_Avatax;

defined( 'ABSPATH' ) or exit;

/**
 * The AvaTax API address request class.
 *
 * @since 1.0.0
 */
class RequestTax extends Request {

	/**
	 * Get the calculated tax for the current cart at checkout.
	 *
	 * @param $post_data
	 * @since 1.0.0
	 * @throws Exception
	 */
	public function set_checkout_parameters( $post_data = null ) {

		if ( empty( $post_data ) ) {
			$post_data = $_POST;
		}

		$args = array();

		if ( is_user_logged_in() ) {
			$args['customerCode'] = wp_get_current_user()->user_email;
		} elseif ( ! empty( $_POST['rcp_user_email'] ) ) {
			$args['customerCode'] = $_POST['rcp_user_email'];
		}

		$args['addresses'] = array(
			'ShipTo' => $this->prepare_address( $post_data ),
		);

		$args['lines'] = $this->prepare_lines();

		// Set the VAT if it exists
		if ( $vat = Helpers::get_param( $post_data, 'rcp_vat_id' ) ) {
			$args['businessIdentificationNo'] = $vat;
		}

		$this->set_params( $args );

	}

	/**
	 * Get the calculated tax for the current cart at checkout.
	 *
	 * @param $payment_args
	 * @since 1.0.0
	 * @throws Exception
	 */
	public function set_payment_parameters( $payment_args ) {

		$defaults = array(
			'payment_id'   => 0,
			'subscription' => '',
			'amount'       => 0,
			'user_id'      => 0,
			'status'       => 'pending',
		);

		$payment_args = wp_parse_args( (array) $payment_args, $defaults );

		if ( ! $subscription = rcp_get_subscription_details_by_name( $payment_args['subscription'] ) ) {
			throw new Exception( 'The subscription level is invalid.' );
		}

		$args = array(
			'commit'       => $this->commit_calculations(),
			'code'         => $payment_args['payment_id'],
			'type'         => 'SalesInvoice',
			'customerCode' => get_userdata( $payment_args['user_id'] )->user_email,
		);

		$address           = rcp_avatax()->member_fields->get_user_address( $payment_args['user_id'] );
		$args['addresses'] = array(
			'ShipFrom' => $this->get_company_address(),
			'ShipTo'   => $this->prepare_address( $address ),
		);

		// Set the VAT if it exists
		if ( $vat = Helpers::get_param( $payment_args, 'rcp_vat_id' ) ) {
			$args['businessIdentificationNo'] = $vat;
		}

		if ( ! $item = RCP_Avatax::meta_get( $subscription->id, 'item' ) ) {
			throw new Exception( 'This subscription level does not have a related AvaTax item.' );
		}

		$line = array(
			'id'          => $subscription->id,
			'quantity'    => 1,
			'amount'      => $payment_args['amount'],
			'itemCode'    => $item,
			'description' => $subscription->description,
			'taxIncluded' => true,
		);

		if ( $tax_code = RCP_Avatax::meta_get( $subscription->id, 'item' ) ) {
			$line['taxCode'] = $tax_code;
		}

		$args['lines'] = array( $line );

		// Set the VAT if it exists
		if ( $vat = Helpers::get_param( $address, 'rcp_vat_id' ) ) {
			$args['businessIdentificationNo'] = $vat;
		}

		$this->set_params( $args );

	}

	/**
	 * Set the calculation request params.
	 *
	 * @since 1.0.0
	 * @param array $args {
	 *     The AvaTax API parameters.
	 *
	 *     @type int    $code         The unique transaction ID.
	 *     @type string $customerCode The unique customer identifier.
	 *     @type array  $addresses    The origin and destination addresses. @see `Request::prepare_address()` for formatting.
	 *     @type array  $lines        The line items used for calculation. @see `Request::prepare_line()` for formatting.
	 *     @type string $date         The document creation date. Format: YYYY-MM-DD. Default: the current date.
	 *     @type string $taxDate      The effective tax date. Format: YYYY-MM-DD.
	 *     @type string $type         The type of Document requested of AvaTax.
	 *     @type string $currencyCode The calculation currency code. Default: the shop currency code.
	 *     @type bool   $exemption    Whether the transaction has tax exemption.
	 *     @type bool   $commit       Whether to commit this calculation as a finalized transaction. Default: `false`.
	 *     @type string $businessIdentificationNo The customer's VAT ID.
	 * }
	 */
	public function set_params( $args ) {

		$defaults = array(
			'type'                     => 'SalesOrder',
			'code'                     => null,
			'companyCode'              => RCP_Avatax::get_settings( 'avatax_company_code' ),
			'date'                     => date( 'Y-m-d', current_time( 'timestamp' ) ),
			'customerCode'             => '99999',
			'discount'                 => null,
			'addresses'                => array(
				'ShipFrom' => array(),
				'ShipTo'   => array(),
			),
			'lines'                    => array(),
			'commit'                   => false,
			'taxDate'                  => '',
			'currencyCode'             => rcp_get_currency(),
			'businessIdentificationNo' => false,
		);

		$params = apply_filters( 'rcp_avatax_set_params_tax', wp_parse_args( $args, $defaults ), $args );

		$this->path   = 'transactions/create/';
		$this->params = $params;
	}


	/**
	 * Prepare an order line item for the AvaTax API.
	 *
	 * @since 1.0.0
	 * @param $subscription_id
	 * @return array $line The formatted line.
	 * @throws Exception
	 */
	protected function prepare_lines( $subscription_id = null ) {

		if ( ! $subscription_id ) {
			$subscription_id = rcp_get_registration()->get_subscription();
		}

		if ( ! $item = RCP_Avatax::meta_get( $subscription_id, 'item' ) ) {
			throw new Exception( 'This subscription level does not have a related AvaTax item.' );
		}

		$args = array(
			'quantity' => 1,
			'itemCode' => $item,
		);

		if ( $tax_code = RCP_Avatax::meta_get( $subscription_id, 'item' ) ) {
			$args['taxCode'] = $tax_code;
		}

		$lines = array();

		// total today tax
		$args['amount'] = rcp_get_registration_total();
		$lines[]        = $args;

		// total recurring tax
		$args['amount'] = rcp_get_registration_recurring_total();
		$lines[]        = $args;

		return apply_filters( 'rcp_avatax_prepare_line', $lines, $subscription_id );
	}

	/**
	 * Determine if new tax documents should be committed on calculation.
	 *
	 * @since 1.0.0
	 * @return bool $commit Whether new tax documents should be committed on calculation.
	 */
	protected function commit_calculations() {
		$disable_commit = RCP_Avatax::get_settings( 'disable_commit', false );
		/**
		 * Filter whether new tax documents should be committed on calculation.
		 *
		 * @since 1.0.0
		 * @param bool $commit Whether new tax documents should be committed on calculation.
		 */
		return (bool) apply_filters( 'rcp_avatax_commit_calculations', ! $disable_commit );
	}
}

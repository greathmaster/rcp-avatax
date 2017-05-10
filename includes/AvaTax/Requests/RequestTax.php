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

use SkilledCode\Helpers;
use SkilledCode\RequestAPI\Exception;

defined( 'ABSPATH' ) or exit;

/**
 * The AvaTax API address request class.
 *
 * @since 1.0.0
 */
class RequestTax extends Request {

	/**
	 * Construct the tax request object.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->method = 'POST';
	}


	/**
	 * Get the calculated tax for the current cart at checkout.
	 *
	 * @param $post_data
	 * @since 1.0.0
	 * @throws \Exception
	 * @throws \SkilledCode\Exception
	 */
	public function process_checkout( $post_data = null ) {

		if ( empty( $post_data ) ) {
			$post_data = $_POST;
		}

		$args = array();

		try {

			$args['addresses'] = array(
				'SingleLocation' => $this->prepare_address( $post_data ),
			);

			$args['lines'] = $this->prepare_lines();

			// Set the VAT if it exists
			if ( $vat = Helpers::get_param( $post_data, 'rcp_vat_id' ) ) {
				$args['businessIdentificationNo'] = $vat;
			}

			$this->set_params( $args );

		} catch ( \SkilledCode\Exception $e ) {
			throw $e;
		}

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
			'companyCode'              => rcp_avatax()::get_settings( 'avatax_company_code' ),
			'date'                     => date( 'Y-m-d', current_time( 'timestamp' ) ),
			'customerCode'             => '99999',
			'discount'                 => null,
			'addresses'                => array(
				'SingleLocation' => array(),
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

		if ( ! $item = rcp_avatax()::meta_get( $subscription_id, 'avatax-item' ) ) {
			throw new Exception( 'This subscription level does not have a related AvaTax item.' );
		}

		$total           = rcp_get_registration_total();
		$total_recurring = rcp_get_registration_recurring_total();

		$lines = array();

		$lines[] = array(
			'quantity' => 1,
			'amount'   => $total,
			'itemCode' => $item,
		);

		$lines[] = array(
			'quantity' => 1,
			'amount'   => $total_recurring,
			'itemCode' => $item,
		);

		return apply_filters( 'rcp_avatax_prepare_line', $lines, $subscription_id );
	}

	/**
	 * Determine if new tax documents should be committed on calculation.
	 *
	 * @since 1.0.0
	 * @return bool $commit Whether new tax documents should be committed on calculation.
	 */
	protected function commit_calculations() {

		/**
		 * Filter whether new tax documents should be committed on calculation.
		 *
		 * @since 1.0.0
		 * @param bool $commit Whether new tax documents should be committed on calculation.
		 */
		return (bool) apply_filters( 'rcp_avatax_commit_calculations', ( 'yes' === get_option( 'wc_avatax_commit' ) ) );
	}
}

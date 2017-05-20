<?php
/**
 * Define the Request class
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace RCP_Avatax\AvaTax\Requests;

use SkilledCode\RequestAPI\RequestJSON;
use SkilledCode\RequestAPI\Exception;
use SkilledCode\Helpers;
use RCP_Avatax\Init as RCP_Avatax;

defined( 'ABSPATH' ) or exit;

/**
 * The AvaTax API request class.
 *
 * @since 1.0.0
 */
class Request extends RequestJSON {


	/**
	 * Construct the AvaTax request object.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->method = 'POST';
	}

	/**
	 * Void a document in Avalara.
	 *
	 * @since 1.0.0
	 * @param int $doc_code The document code.
	 * @param string $origin Optional. Whether the document came from an order or refund
	 */
	public function void_document( $doc_code, $origin = 'order' ) {

		$this->method = 'POST';
		$this->path   = 'tax/cancel';
		$this->params = array(
			'CancelCode'  => 'DocVoided',
			'CompanyCode' => Helpers::str_truncate( RCP_Avatax::get_settings( 'avatax_company_code' ), 25, '' ),
			'DocCode'     => Helpers::str_truncate( $doc_code, 50, '' ),
			'DocType'     => 'SalesInvoice',
		);

		if ( 'refund' === $origin ) {
			$this->params['DocType'] = 'ReturnInvoice';
		} else {
			$this->params['DocType'] = 'SalesInvoice';
		}
	}


	/**
	 * Test the API credentials.
	 *
	 * @since 1.0.0
	 * @param $company
	 */
	public function test( $company ) {
		$this->method = 'GET';
		$this->path = 'utilities/ping';
	}


	/**
	 * Prepare an address for the AvaTax API.
	 *
	 * Instead of keeping the input array keys 1-to-1 with the AvaTax API param keys, we map them to
	 * RCP's standard address keys to make things easier on the RCP side and avoid
	 * extra changes if the AvaTax API changes.
	 *
	 * @since 1.0.0
	 * @param array $address The address details. @see `API::validate_address()` for formatting.
	 * @param string $id Optional. The unique address ID.
	 * @return array The formatted address.
	 * @throws Exception
	 */
	protected function prepare_address( $address, $id = '' ) {

		$defaults = array(
			'rcp_card_address'   => '',
			'rcp_card_address_2' => '',
			'rcp_card_city'      => '',
			'rcp_card_state'     => '',
			'rcp_card_country'   => '',
			'rcp_card_zip'       => '',
		);

		$address = wp_parse_args( (array) $address, $defaults );

		$address = array(
			'Line1'       => Helpers::str_truncate( $address['rcp_card_address'], 50 ),
			'Line2'       => Helpers::str_truncate( $address['rcp_card_address_2'], 50 ),
			'City'        => Helpers::str_truncate( $address['rcp_card_city'], 50 ),
			'Region'      => Helpers::str_truncate( $address['rcp_card_state'], 3, '' ),
			'Country'     => Helpers::str_truncate( $address['rcp_card_country'], 2, '' ),
			'PostalCode'  => Helpers::str_truncate( $address['rcp_card_zip'], 11, '' ),
		);

		// Add the unique ID if set
		if ( $id ) {
			$address['AddressCode'] = $id;
		}

		$address = apply_filters( 'rcp_avatax_prepare_address', $address );

		if ( empty( $address['Line1'] ) || empty( $address['City'] ) || empty( $address['Region'] ) || empty( $address['Country'] ) ) {
			throw new Exception( 'Missing required Address parameters. Please provide Line1, City, Region, and Country' );
		}

		return $address;
	}


}

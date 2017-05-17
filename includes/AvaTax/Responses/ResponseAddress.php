<?php
/**
 * Define the ResponseAddress class
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace RCP_Avatax\AvaTax\Responses;

use RCP_Avatax\AvaTax\Responses\Response;

defined( 'ABSPATH' ) or exit;

/**
 * The AvaTax API address response class.
 *
 * @since 1.0.0
 */
class ResponseAddress extends Response {


	/**
	 * Get the normalized address data.
	 *
	 * @since 1.0.0
	 * @return array The normalized address data.
	 */
	public function get_normalized_address() {

		$data = $this->response_data->address;

		// Map the API response to their proper keys
		$address = array(
			'rcp_card_address'   => $data->line1,
			'rcp_card_address_2' => $data->line2,
			'rcp_card_city'      => $data->city,
			'rcp_card_state'     => $data->region,
			'rcp_card_country'   => $data->country,
			'rcp_card_zip'       => $data->postalCode,
		);

		// Make sure the address values are squeaky clean
		$address = array_map( 'sanitize_text_field', $address );

		return $address;
	}

	public function get_validation_messages( $severity = 'Error' ) {

		$messages          = array();
		$response_messages = empty( $this->response_data->messages ) ? array() : $this->response_data->messages;

		foreach( (array) $response_messages as $message ) {
			if ( $severity && $severity != $message->severity ) {
				continue;
			}

			$messages[ $message->summary ] = $message->details;
		}

		return apply_filters( 'rcp_avatax_response_address_validation_messages', $messages, $severity, $this );
	}
}

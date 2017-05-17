<?php
/**
 * Define the ResponseTax class
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
 * The AvaTax API tax response class.
 *
 * @since 1.0.0
 */
class ResponseTax extends Response {


	/**
	 * Get the tax details.
	 *
	 * @since 1.0.0
	 * @return array The calculated line item
	 */
	public function get_details() {

		if ( empty( $this->response_data ) ) {
			return array();
		}

		$details = array(
			'taxable' => sanitize_text_field( $this->get_detail( 'totalTaxable' ) ),
			'tax'     => sanitize_text_field( $this->get_detail( 'totalTax' ) ),
			'rate'    => round( $this->get_detail( 'taxRate' ), 4 ),
		);

		return apply_filters( 'rcp_avatax_response_tax_get_details', $details, $this );
	}

	/**
	 * Get response detail
	 *
	 * @param $var
	 *
	 * @return mixed
	 */
	public function get_detail( $var ) {

		$data = $this->response_data;

		switch( $var ) {
			case 'taxRate' :
				$value = 0;

				foreach( (array) $data->lines[0]->details as $detail ) {
					$value += $detail->rate;
				}

				break;
			default :
				$value = $data->$var;
				break;
		}

		return apply_filters( 'rcp_avatax_response_tax_get_detail', $value, $var, $this );
	}

}

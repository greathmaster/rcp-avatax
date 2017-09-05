<?php

namespace RCP_Avatax;

class Payments {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of self
	 *
	 * @return Payments
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Payments ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_action( 'get_template_part_invoice', array( $this, 'setup_invoice_data' ) );
	}

	/**
	 * Update the $rcp_payment data to show the correct information on the invoice
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function setup_invoice_data() {
		global $rcp_payment;

		if ( ! $payment_tax = self::get_tax( $rcp_payment->id ) ) {
			return;
		}

		// store the original total and tax
		$rcp_payment->total = $rcp_payment->amount;
		$rcp_payment->tax   = $payment_tax;
		$rcp_payment->amount -= $payment_tax;

		add_action( 'rcp_invoice_items', array( $this, 'invoice_item' ) );
	}

	/**
	 * Add tax item to the invoice
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function invoice_item() {
		global $rcp_payment;

		if ( empty( $rcp_payment->tax ) ) {
			return;
		} ?>
		<tr>
			<td class="name"><?php echo apply_filters( 'rcp_avatax_invoice_tax_label', __( 'Tax', 'rcp-avatax' ) ); ?></td>
			<td class="price"><?php echo rcp_currency_filter( number_format( $rcp_payment->tax, rcp_currency_decimal_filter() ) ); ?></td>
		</tr>
		<?php

		// restore the original total amount
		$rcp_payment->amount = $rcp_payment->total;
	}

	/**
	 * Get the tax for the provided payment ID
	 *
	 * @param int $payment_id
	 *
	 * @since  1.0.0
	 *
	 * @return int
	 * @author Tanner Moushey
	 */
	public static function get_tax( $payment_id ) {
		global $rcp_payments_db;

		$tax = 0;

		if ( ( $details = $rcp_payments_db->get_meta( $payment_id, 'tax_details', true ) ) && isset( $details['tax'] ) ) {
			$tax = $details['tax'];
		}

		/**
		 * Filter the tax calculated for this payment
		 *
		 * @param int $tax The tax calculated for this payment
		 * @param int $payment_id The id for this payment
		 */
		return apply_filters( 'rcp_avatax_get_payment_tax', $tax, $payment_id );
	}

}
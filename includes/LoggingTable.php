<?php
/**
 * Logs List Table
 */

namespace RCP_Avatax;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Extend the WP_List_Table class
 *
 * @access      public
 * @since       1.0
 * @return      void
 */
class LoggingTable extends \WP_List_Table {

	/**
	 * LoggingTable constructor.
	 */
	function __construct() {
		global $status, $page;

		//Set parent defaults
		parent::__construct( array(
			'singular' => 'log',     //singular name of the listed records
			'plural'   => 'logs',    //plural name of the listed records
			'ajax'     => false      //does this table support ajax?
		) );

	}

	/**
	 * Render the column contents
	 *
	 * @return      string The contents of each column
	 */
	function column_default( $item, $column_name ) {

		switch ( $column_name ) {

			case 'message' :

				return get_post_field( 'post_content', $item->ID );

			case 'code' :

				return get_post_meta( $item->ID, '_log_response_code', true );

			case 'uri' :

				return get_post_meta( $item->ID, '_log_request_uri', true );

			case 'duration' :

				return get_post_meta( $item->ID, '_log_request_duration', true );

			case 'date' :
				$date = strtotime( get_post_field( 'post_date', $item->ID ) );
				$date = date_i18n( get_option( 'date_format' ), $date ) . ' ' . __( 'at', 'rcp-avatax' ) . ' ' . date_i18n( get_option( 'time_format' ), $date );
				return sprintf( '<a href="%s">%s</a>', add_query_arg( 'view-log', $item->ID ), $date );

		}
	}


	/**
	 * Render the checbox column
	 *
	 * @return      string HTML Checkbox
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ 'log',
			/*$2%s*/ $item->ID
		);
	}


	/**
	 * Setup our table columns
	 *
	 * @return      array
	 */
	function get_columns() {
		$columns = array(
			'cb'       => '<input type="checkbox" />', //Render a checkbox instead of text
			'date'     => __( 'Date', 'rcp-avatax' ),
			'code'     => __( 'Response Code', 'rcp-avatax' ),
			'uri'      => __( 'Request URI', 'rcp-avatax' ),
			'duration' => __( 'Request Duration', 'rcp-avatax' ),
//			'user_id'   => __( 'User', 'rcp-avatax' ),
			'message'  => __( 'Message', 'rcp-avatax' )
		);

		return $columns;
	}


	/**
	 * Register our bulk actions
	 *
	 * @return      array
	 */
	function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete', 'rcp-avatax' ),
		);

		return $actions;
	}


	/**
	 * Process bulk action requests
	 *
	 * @return      void
	 */
	function process_bulk_action() {

		$ids = isset( $_GET['log'] ) ? $_GET['log'] : false;

		if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'bulk-logs' ) ) {
			return;
		}

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		foreach ( $ids as $id ) {
			// Detect when a bulk action is being triggered...
			if ( 'delete' === $this->current_action() ) {
				wp_delete_post( $id );
			}

		}
	}


	/**
	 * Load all of our data
	 *
	 * @return      void
	 */
	function prepare_items() {

		/**
		 * First, lets decide how many records per page to show
		 */
		$per_page = 20;

		$paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;

		$columns = $this->get_columns();

		$hidden = array(); // no hidden columns

		$this->_column_headers = array( $columns, $hidden, array() );

		$this->process_bulk_action();

		$meta_query = array();
		if ( isset( $_GET['user'] ) ) {
			$meta_query[] = array(
				'key'   => '_wp_log_user_id',
				'value' => absint( $_GET['user'] )
			);
		}

		$this->items = rcp_avatax()->logging->get_connected_logs( array(
			'log_type'       => null,
			'paged'          => $paged,
			'posts_per_page' => $per_page,
			'meta_query'     => $meta_query
		) );


		$current_page = $this->get_pagenum();

		$total_items = rcp_avatax()->logging->get_log_count( 0, null, $meta_query );

		$this->set_pagination_args( array(
			'total_items' => $total_items,                    //WE have to calculate the total number of items
			'per_page'    => $per_page,                       //WE have to determine how many items to show on a page
			'total_pages' => ceil( $total_items / $per_page ) //WE have to calculate the total number of pages
		) );
	}

}
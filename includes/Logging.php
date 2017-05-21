<?php

namespace RCP_Avatax;

class Logging {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * @var HandleTaxes
	 */
	public $handle_taxes;

	/**
	 * @var MemberFields
	 */
	public $member_fields;

	/**
	 * Only make one instance of \RCP_Avatax\Logging
	 *
	 * @return Logging
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Logging ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		// Create the log post type
		add_action( 'init', array( $this, 'register_post_type' ), 1 );

		// Create types taxonomy and default types
		add_action( 'init', array( $this, 'register_taxonomy' ), 1 );

		add_action( 'sc_' . rcp_avatax()->get_id() . '_api_request_performed', array( $this, 'log_api_request' ), 10, 3 );
	}


	/**
	 * Registers the rcp_avatax_log Post Type
	 */
	public function register_post_type() {
		/* Logs post type */
		$log_args = array(
			'labels'              => array( 'name' => __( 'AvaTax Logs', 'rcp-avatax' ) ),
			'public'              => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'show_ui'             => false,
			'query_var'           => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'supports'            => array( 'title', 'custom_fields' ),
			'can_export'          => true,
		);

		register_post_type( 'rcp_avatax_log', $log_args );
	}

	/**
	 * Registers the Type Taxonomy
	 *
	 * The "Type" taxonomy is used to determine the type of log entry
	*/
	public function register_taxonomy() {
		register_taxonomy( 'rcp_avatax_log_type', 'rcp_avatax_log', array( 'public' => false ) );
	}

	/**
	 * Log types
	 *
	 * Sets up the default log types and allows for new ones to be created
	 *
	 * @return  array $terms
	 */
	public function log_types() {
		$terms = array(
			'test', 'verify_address', 'calculate_tax', 'record_tax', 'api_error'
		);

		return apply_filters( 'rcp_avatax_log_types', $terms );
	}

	/**
	 * Check if a log type is valid
	 *
	 * Checks to see if the specified type is in the registered list of types
	 * @param string $type Log type
	 * @return bool Whether log type is valid
	 */
	function valid_type( $type ) {
		return in_array( $type, $this->log_types() );
	}

	/**
	 * Create new log entry
	 *
	 * This is just a simple and fast way to log something. Use $this->insert_log()
	 * if you need to store custom meta data
	 *
	 * @param string $title Log entry title
	 * @param string $message Log entry message
	 * @param int $parent Log entry parent
	 * @param string $type Log type (default: null)
	 * @return int Log ID
	 */
	public function add( $title = '', $message = '', $parent = 0, $type = null ) {
		$log_data = array(
			'post_title'   => $title,
			'post_content' => $message,
			'post_parent'  => $parent,
			'log_type'     => $type,
		);

		return $this->insert_log( $log_data );
	}

	/**
	 * Easily retrieves log items for a particular object ID
	 *
	 * @param int $object_id (default: 0)
	 * @param string $type Log type (default: null)
	 * @param int $paged Page number (default: null)
	 * @return array Array of the connected logs
	*/
	public function get_logs( $object_id = 0, $type = null, $paged = null ) {
		return $this->get_connected_logs( array( 'post_parent' => $object_id, 'paged' => $paged, 'log_type' => $type ) );
	}

	/**
	 * Stores a log entry
	 *
	 * @param array $log_data Log entry data
	 * @param array $log_meta Log entry meta
	 * @return int The ID of the newly created log item
	 */
	function insert_log( $log_data = array(), $log_meta = array() ) {
		$defaults = array(
			'post_type'    => 'rcp_avatax_log',
			'post_status'  => 'publish',
			'post_parent'  => 0,
			'post_content' => '',
			'log_type'     => false,
		);

		$args = wp_parse_args( $log_data, $defaults );

		do_action( 'rcp_avatax_pre_insert_log', $log_data, $log_meta );

		// Store the log entry
		$log_id = wp_insert_post( $args );

		// Set the log type, if any
		if ( $log_data['log_type'] && $this->valid_type( $log_data['log_type'] ) ) {
			wp_set_object_terms( $log_id, $log_data['log_type'], 'rcp_avatax_log_type', false );
		}

		// Set log meta, if any
		if ( $log_id && ! empty( $log_meta ) ) {
			foreach ( (array) $log_meta as $key => $meta ) {
				update_post_meta( $log_id, '_log_' . sanitize_key( $key ), $meta );
			}
		}

		do_action( 'rcp_avatax_post_insert_log', $log_id, $log_data, $log_meta );

		return $log_id;
	}

	/**
	 * Update and existing log item
	 *
	 * @param array $log_data Log entry data
	 * @param array $log_meta Log entry meta
	 * @return int The ID of the updated log item
	 */
	public function update_log( $log_data = array(), $log_meta = array() ) {

		do_action( 'rcp_avatax_pre_update_log', $log_data, $log_meta );

		$defaults = array(
			'post_type'   => 'rcp_avatax_log',
			'post_status' => 'publish',
			'post_parent' => 0,
		);

		$args = wp_parse_args( $log_data, $defaults );

		// Store the log entry
		$log_id = wp_update_post( $args );

		if ( $log_id && ! empty( $log_meta ) ) {
			foreach ( (array) $log_meta as $key => $meta ) {
				if ( ! empty( $meta ) )
					update_post_meta( $log_id, '_log_' . sanitize_key( $key ), $meta );
			}
		}

		do_action( 'rcp_avatax_post_update_log', $log_id, $log_data, $log_meta );

		return $log_id;
	}

	/**
	 * Retrieve all connected logs
	 *
	 * Used for retrieving logs related to particular items, such as a specific purchase.
	 *
	 * @param array $args Query arguments
	 * @return mixed array if logs were found, false otherwise
	 */
	public function get_connected_logs( $args = array() ) {
		$defaults = array(
			'post_type'      => 'rcp_avatax_log',
			'posts_per_page' => 20,
			'post_status'    => 'publish',
			'paged'          => get_query_var( 'paged' ),
			'log_type'       => false,
		);

		$query_args = wp_parse_args( $args, $defaults );

		if ( $query_args['log_type'] && $this->valid_type( $query_args['log_type'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy'  => 'rcp_avatax_log_type',
					'field'     => 'slug',
					'terms'     => $query_args['log_type'],
				)
			);
		}

		$logs = get_posts( $query_args );

		if ( $logs )
			return $logs;

		// No logs found
		return false;
	}

	/**
	 * Retrieves number of log entries connected to particular object ID
	 *
	 * @param int $object_id (default: 0)
	 * @param string $type Log type (default: null)
	 * @param array $meta_query Log meta query (default: null)
	 * @param array $date_query Log data query (default: null) (since 1.9)
	 * @return int Log count
	 */
	public function get_log_count( $object_id = 0, $type = null, $meta_query = null, $date_query = null ) {

		$query_args = array(
			'post_parent'      => $object_id,
			'post_type'        => 'rcp_avatax_log',
			'posts_per_page'   => -1,
			'post_status'      => 'publish',
			'fields'           => 'ids',
		);

		if ( ! empty( $type ) && $this->valid_type( $type ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy'  => 'rcp_avatax_log_type',
					'field'     => 'slug',
					'terms'     => $type,
				)
			);
		}

		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query;
		}

		if ( ! empty( $date_query ) ) {
			$query_args['date_query'] = $date_query;
		}

		$logs = new \WP_Query( $query_args );

		return (int) $logs->post_count;
	}

	/**
	 * Delete a log
	 *
	 * @param int $object_id (default: 0)
	 * @param string $type Log type (default: null)
	 * @param array $meta_query Log meta query (default: null)
	 * @return void
	 */
	public function delete_logs( $object_id = 0, $type = null, $meta_query = null  ) {
		$query_args = array(
			'post_parent'    => $object_id,
			'post_type'      => 'rcp_avatax_log',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		);

		if ( ! empty( $type ) && $this->valid_type( $type ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy'  => 'rcp_avatax_log_type',
					'field'     => 'slug',
					'terms'     => $type,
				)
			);
		}

		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query;
		}

		$logs = get_posts( $query_args );

		if ( $logs ) {
			foreach ( $logs as $log ) {
				wp_delete_post( $log, true );
			}
		}
	}

	/**
	 * Log the api request
	 *
	 * @param $request_data
	 * @param $response_data
	 * @param $request_api
	 */
	public function log_api_request( $request_data, $response_data, $request_api ) {
		$log_data = array(
			'post_content' => $response_data['message'],
		);

		$response_data['body'] = json_decode( wp_remote_retrieve_body( $response_data ) );
		$request_data['body']  = json_decode( wp_remote_retrieve_body( $request_data ) );

		$log_meta = array(
			'response_code'    => $response_data['code'],
			'request_duration' => $request_data['duration'],
			'request_uri'      => $request_data['uri'],
			'response_data'    => maybe_serialize( $response_data ),
			'request_data'     => maybe_serialize( $request_data ),
		);

		$this->insert_log( $log_data, $log_meta );
	}

}
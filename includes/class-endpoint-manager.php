<?php
class WP_API_Tester_Endpoint_Manager {
	private $dynamic_handler;

	public function __construct() {
		$this->dynamic_handler = new WP_API_Tester_Dynamic_Endpoint_Handler();
	}

	public function get_endpoints() {
		$endpoints = get_option( 'wp_api_tester_endpoints', array() );
		return is_array( $endpoints ) ? $endpoints : array();
	}

	public function create_endpoint( $route, $method, $permission, $queryData, $response ) {
		if ( empty( $route ) || empty( $method ) ) {
			return false;
		}

		$endpoints   = $this->get_endpoints();
		$endpoints[] = array(
			'route'      => sanitize_text_field( $route ),
			'method'     => sanitize_text_field( $method ),
			'permission' => sanitize_text_field( $permission ),
			'queryData'  => $queryData,
			'response'   => $response,
		);

		$success = update_option( 'wp_api_tester_endpoints', $endpoints );

		if ( $success ) {
			// Trigger WordPress to flush rewrite rules
			flush_rewrite_rules();

			// Re-register all dynamic endpoints
			$this->dynamic_handler->register_dynamic_endpoints();
		}

		return $success;
	}
}

<?php
class WP_API_Tester_Dynamic_Endpoint_Handler {
	private $database_manager;

	public function __construct() {
		$this->database_manager = new WP_API_Tester_Database_Manager();
	}

	public function register_dynamic_endpoints() {
		$endpoints = get_option( 'wp_api_tester_endpoints', array() );

		foreach ( $endpoints as $endpoint ) {
			$this->register_single_endpoint( $endpoint );
		}
	}

	private function register_single_endpoint( $endpoint ) {
		$route = trim( str_replace( '/wp-json/wp-api-tester/v1', '', $endpoint['route'] ), '/' );

		register_rest_route(
			'wp-api-tester/v1',
			'/' . $route,
			array(
				'methods'             => $endpoint['method'],
				'callback'            => function ( $request ) use ( $endpoint ) {
					return $this->handle_endpoint_request( $endpoint, $request );
				},
				'permission_callback' => function () use ( $endpoint ) {
					return $this->check_endpoint_permission( $endpoint['permission'] );
				},
			)
		);
	}

	private function handle_endpoint_request( $endpoint, $request ) {
		if ( empty( $endpoint['queryData'] ) ) {
			return rest_ensure_response( $endpoint['response'] );
		}

		// Ensure queryData has the required structure
		$queryData = $endpoint['queryData'];
		if ( ! isset( $queryData['queryType'] ) ) {
			$queryData['queryType'] = isset( $queryData['table'] ) ? 'table' : 'custom';
		}

		// For table queries
		if ( $queryData['queryType'] === 'table' && isset( $queryData['table'] ) ) {
			$params = array(
				'queryType' => 'table',
				'table'     => $queryData['table'],
				'columns'   => $queryData['columns'] ?? array( '*' ),
			);
		}
		// For custom queries
		elseif ( $queryData['queryType'] === 'custom' && isset( $queryData['query'] ) ) {
			$params = array(
				'queryType' => 'custom',
				'query'     => $queryData['query'],
			);
		}
		// Default response if query data is invalid
		else {
			return rest_ensure_response( $endpoint['response'] );
		}

		$results = $this->database_manager->test_query( $params );

		if ( is_wp_error( $results ) ) {
			return $results;
		}

		return rest_ensure_response( $results );
	}

	private function check_endpoint_permission( $permission ) {
		switch ( $permission ) {
			case 'none':
				return true;
			case 'user':
				return is_user_logged_in();
			case 'editor':
				return current_user_can( 'edit_posts' );
			case 'admin':
				return current_user_can( 'manage_options' );
			default:
				return true;
		}
	}
}

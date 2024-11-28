<?php
class WP_API_Tester_Database_Manager {
	public function get_tables() {
		global $wpdb;
		$tables = $wpdb->get_results( 'SHOW TABLES', ARRAY_N );
		return array_map(
			function ( $table ) {
				return $table[0];
			},
			$tables
		);
	}

	public function get_columns( $table ) {
		global $wpdb;
		$table   = sanitize_text_field( $table );
		$columns = $wpdb->get_results( "SHOW COLUMNS FROM `$table`" );
		return array_map(
			function ( $column ) {
				return $column->Field;
			},
			$columns
		);
	}

	public function test_query( $params ) {
		global $wpdb;

		if ( ! isset( $params['queryType'] ) ) {
			return new WP_Error( 'invalid_params', 'Query type is required' );
		}

		if ( $params['queryType'] === 'table' ) {
			return $this->handle_table_query( $params );
		} else {
			return $this->handle_custom_query( $params );
		}
	}

	private function handle_table_query( $params ) {
		global $wpdb;

		if ( empty( $params['table'] ) ) {
			return new WP_Error( 'missing_table', 'Table name is required' );
		}

		$table   = sanitize_text_field( $params['table'] );
		$columns = isset( $params['columns'] ) ? array_map( 'sanitize_text_field', $params['columns'] ) : array( '*' );

		$selected_columns = implode(
			', ',
			array_map(
				function ( $col ) {
					return $col === '*' ? '*' : "`$col`";
				},
				$columns
			)
		);

		$query = "SELECT $selected_columns FROM `$table` LIMIT 5";
		return $wpdb->get_results( $query );
	}

	private function handle_custom_query( $params ) {
		global $wpdb;

		if ( empty( $params['query'] ) ) {
			return new WP_Error( 'missing_query', 'SQL query is required' );
		}

		$query = $params['query'];

		// Security checks
		if ( ! $this->is_select_query( $query ) ) {
			return new WP_Error( 'invalid_query', 'Only SELECT queries are allowed' );
		}

		return $wpdb->get_results( $query );
	}

	private function is_select_query( $query ) {
		$query = trim( strtoupper( $query ) );
		return strpos( $query, 'SELECT' ) === 0
			&& strpos( $query, 'INSERT' ) === false
			&& strpos( $query, 'UPDATE' ) === false
			&& strpos( $query, 'DELETE' ) === false
			&& strpos( $query, 'DROP' ) === false
			&& strpos( $query, 'TRUNCATE' ) === false
			&& strpos( $query, 'ALTER' ) === false;
	}
}

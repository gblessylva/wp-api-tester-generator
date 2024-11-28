<?php
class WP_API_Tester_REST_Controller {
    private $endpoint_manager;
    private $database_manager;

    public function __construct() {
        $this->endpoint_manager = new WP_API_Tester_Endpoint_Manager();
        $this->database_manager = new WP_API_Tester_Database_Manager();
    }

    public function register_routes() {
        register_rest_route('wp-api-tester/v1', '/endpoints', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_endpoints'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_endpoint'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
        ]);

        register_rest_route('wp-api-tester/v1', '/tables', [
            'methods' => 'GET',
            'callback' => [$this, 'get_tables'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route('wp-api-tester/v1', '/columns/(?P<table>[a-zA-Z0-9_]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_columns'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route('wp-api-tester/v1', '/test-query', [
            'methods' => 'POST',
            'callback' => [$this, 'test_query'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);
    }

    public function check_admin_permission() {
        return current_user_can('manage_options');
    }

    public function get_endpoints() {
        return rest_ensure_response($this->endpoint_manager->get_endpoints());
    }

    public function create_endpoint($request) {
        $params = $request->get_params();
        
        if (empty($params['route']) || empty($params['method'])) {
            return new WP_Error(
                'missing_params',
                'Required parameters are missing',
                ['status' => 400]
            );
        }

        $success = $this->endpoint_manager->create_endpoint(
            $params['route'],
            $params['method'],
            $params['permission'] ?? 'none',
            $params['queryData'] ?? [],
            $params['response'] ?? []
        );

        if (!$success) {
            return new WP_Error(
                'creation_failed',
                'Failed to create endpoint',
                ['status' => 500]
            );
        }

        return rest_ensure_response([
            'success' => true,
            'message' => 'Endpoint created successfully',
        ]);
    }

    public function get_tables() {
        return rest_ensure_response($this->database_manager->get_tables());
    }

    public function get_columns($request) {
        $table = $request['table'];
        return rest_ensure_response($this->database_manager->get_columns($table));
    }

    public function test_query($request) {
        $params = $request->get_params();
        
        if ($params['queryType'] === 'table' && (empty($params['table']) || empty($params['columns']))) {
            return new WP_Error(
                'missing_params',
                'Table and columns are required for table queries',
                ['status' => 400]
            );
        }

        if ($params['queryType'] === 'custom' && empty($params['query'])) {
            return new WP_Error(
                'missing_params',
                'SQL query is required for custom queries',
                ['status' => 400]
            );
        }

        $results = $this->database_manager->test_query($params);
        
        if (is_wp_error($results)) {
            return $results;
        }
        
        return rest_ensure_response($results);
    }
}
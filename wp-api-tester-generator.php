<?php
/**
 * Plugin Name: WP API Tester & Generator
 * Description: Create and test custom REST API endpoints with a visual interface
 * Version: 1.0.0
 * Author: gblessyla
 * License: GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-endpoint-manager.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-rest-controller.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-database-manager.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-dynamic-endpoint-handler.php';

/**
 * Class WP_API_Tester_Generator
 */
class WP_API_Tester_Generator {
	/**
	 * @var mixed instance
	 */
	private static $instance = null;
	private $rest_controller;
	private $dynamic_handler;

	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * __construct
	 *
	 * @return void
	 */
	private function __construct() {
		$this->rest_controller = new WP_API_Tester_REST_Controller();
		$this->dynamic_handler = new WP_API_Tester_Dynamic_Endpoint_Handler();
		$this->init();
	}

	/**
	 * init
	 *
	 * @return void
	 */
	private function init() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'rest_api_init', array( $this->rest_controller, 'register_routes' ) );
		add_action( 'rest_api_init', array( $this->dynamic_handler, 'register_dynamic_endpoints' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * add_admin_menu
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_menu_page(
			'API Tester & Generator',
			'API Tester',
			'manage_options',
			'wp-api-tester',
			array( $this, 'render_admin_page' ),
			'dashicons-rest-api'
		);
	}

	public function render_admin_page() {
		require_once plugin_dir_path( __FILE__ ) . 'templates/admin-page.php';
	}

	public function enqueue_admin_assets( $hook ) {
		if ( $hook !== 'toplevel_page_wp-api-tester' ) {
			return;
		}

		wp_enqueue_style(
			'wp-api-tester-styles',
			plugin_dir_url( __FILE__ ) . 'assets/css/admin.css'
		);

		wp_enqueue_script(
			'wp-api-tester-script',
			plugin_dir_url( __FILE__ ) . 'assets/js/admin.js',
			array( 'jquery', 'wp-api' ),
			'1.0.0',
			true
		);

		wp_localize_script(
			'wp-api-tester-script',
			'wpApiTester',
			array(
				'nonce'  => wp_create_nonce( 'wp_rest' ),
				'apiUrl' => rest_url( 'wp-api-tester/v1' ),
			)
		);
	}
}

// Initialize the plugin
add_action(
	'plugins_loaded',
	function () {
		WP_API_Tester_Generator::get_instance();
	}
);

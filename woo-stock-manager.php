<?php
/**
 * Plugin Name: Stock Synchronizer for WooCommerce
 * Plugin URI: https://github.com/bart-jaskulski/woo-stock-manager
 * Description: Stock Manager for WooCommerce.
 * Version: 1.0.0
 * Author: Dentonet
 * Author URI: https://dentonet.pl
 * Developer: Bart Jaskulski
 * Developer URI: https://github.com/bart-jaskulski
 * Text Domain: woocommerce-extension
 * Domain Path: /languages
 *
 * WC requires at least: 5.0
 * WC tested up to: 5.0
 *
 * @package dentonet
 */

namespace Dentonet\WP;

use Dentonet\WP\Woo_Stock_Manager\Component_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class responsible for lauching all components.
 */
class Woo_Stock_Manager {

	/**
	 * Array of injected components
	 *
	 * @var array
	 */
	protected $components = array();

	/**
	 * Create object loading files and inserting components.
	 */
	public function __construct() {
		// Check if WooCommerce is active.
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			$this->loader();
			$this->components = $this->get_components();
		}
	}

	/**
	 * Start each component respecitively.
	 */
	public function initialize() {
		array_walk(
			$this->components,
			function ( Component_Interface $component ) {
				$component->initialize();
			}
		);
	}

	/**
	 * Load all files.
	 * TODO: Switch to autoload or sth later.
	 */
	public function loader() {
		require_once( __DIR__ . '/inc/component-interface.php' );
		require_once( __DIR__ . '/inc/class-stock-options.php' );
	}

	/**
	 * List and instantiate all used components here.
	 *
	 * @return array List of components
	 */
	private function get_components() : array {
		return array(
			new Woo_Stock_Manager\Stock_Options(),
		);
	}

}

/**
 * Start plugin when WooCommerce is up and running.
 */
add_action(
	'woocommerce_loaded',
	function () {
		( new Woo_Stock_Manager() )->initialize();
	}
);

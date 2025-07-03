<?php
/**
 * Plugin Name: Simple Coupon Restrictions
 * Plugin URI: https://tanjirsdev.com
 * Description: Track when customers use specific coupons and block them from using other coupons in future orders.
 * Version: 1.0.0
 * Author: Tanjir Al Mamun
 * Author URI: https://tanjirsdev.com
 * License: GPL v2 or later
 * Text Domain: simple-coupon-restrictions
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'SCR_PLUGIN_FILE', __FILE__ );
define( 'SCR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SCR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SCR_VERSION', '1.0.0' );

// Autoloader for PSR-4
spl_autoload_register( function ( $class ) {
    // Base namespace for the plugin
    $prefix = 'SimpleCouponRestrictions\\';
    $base_dir = SCR_PLUGIN_DIR . 'includes/';

    // Check if the class uses the namespace prefix
    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }

    // Get the relative class name
    $relative_class = substr( $class, $len );

    // Replace namespace separators with directory separators
    $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

    // If the file exists, require it
    if ( file_exists( $file ) ) {
        require $file;
    }
} );

/**
 * Main Simple Coupon Restrictions Class
 */
class Simple_Coupon_Restrictions {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Core plugin class
     */
    private $core;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
            return;
        }
        
        // Initialize the core plugin
        $this->core = new \SimpleCouponRestrictions\Core\Plugin();
        $this->core->init();
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error">';
        echo '<p>' . __( 'Simple Coupon Restrictions requires WooCommerce to be installed and activated.', 'simple-coupon-restrictions' ) . '</p>';
        echo '</div>';
    }
}

// Initialize the plugin
Simple_Coupon_Restrictions::get_instance();

/**
 * Activation hook
 */
register_activation_hook( __FILE__, 'scr_activate' );

function scr_activate() {
    // Create database tables if needed
    \SimpleCouponRestrictions\Core\Activator::activate();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Deactivation hook
 */
register_deactivation_hook( __FILE__, 'scr_deactivate' );

function scr_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
} 
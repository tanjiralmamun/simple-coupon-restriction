<?php

namespace SimpleCouponRestrictions\Core;

/**
 * Main Plugin Class
 */
class Plugin {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize components
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain
        $this->load_textdomain();
        
        // Initialize components
        $this->init_components();
        
        // Hook into WooCommerce
        $this->init_woocommerce_hooks();
    }
    
    /**
     * Load text domain for translations
     */
    private function load_textdomain() {
        load_plugin_textdomain( 'simple-coupon-restrictions', false, dirname( plugin_basename( SCR_PLUGIN_FILE ) ) . '/languages' );
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize coupon tracking
        new CouponTracker();
        
        // Initialize admin interface
        if ( is_admin() ) {
            new \SimpleCouponRestrictions\Admin\AdminController();
        }
        
        // Initialize AJAX handlers
        new \SimpleCouponRestrictions\Ajax\AjaxHandler();
    }
    
    /**
     * Initialize WooCommerce hooks
     */
    private function init_woocommerce_hooks() {
        // Hook into order status changes (HPOS compatible)
        add_action( 'woocommerce_order_status_changed', array( $this, 'handle_order_status_change' ), 10, 4 );
        
        // Additional HPOS hooks for better compatibility
        add_action( 'woocommerce_order_status_completed', array( $this, 'handle_order_completion' ), 10, 2 );
        add_action( 'woocommerce_order_status_processing', array( $this, 'handle_order_completion' ), 10, 2 );
        
        // Hook into coupon validation
        add_filter( 'woocommerce_coupon_is_valid', array( $this, 'check_coupon_restriction' ), 10, 2 );
        
        // Declare HPOS compatibility
        add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );
    }
    
    /**
     * Handle order status changes (HPOS compatible)
     */
    public function handle_order_status_change( $order_id, $old_status, $new_status, $order ) {
        // Only track on completed or processing orders
        if ( in_array( $new_status, array( 'completed', 'processing' ) ) ) {
            $tracker = new CouponTracker();
            $tracker->track_order_coupons( $order );
        }
    }
    
    /**
     * Check coupon restrictions
     */
    public function check_coupon_restriction( $is_valid, $coupon ) {
        $restriction_checker = new RestrictionChecker();
        return $restriction_checker->check_customer_restriction( $is_valid, $coupon );
    }
    
    /**
     * Handle order completion (HPOS compatible)
     */
    public function handle_order_completion( $order_id, $order = null ) {
        // Get order object if not provided (HPOS compatibility)
        if ( ! $order ) {
            $order = wc_get_order( $order_id );
        }
        
        if ( ! $order ) {
            return;
        }
        
        $tracker = new CouponTracker();
        $tracker->track_order_coupons( $order );
    }
    
    /**
     * Declare HPOS compatibility
     */
    public function declare_hpos_compatibility() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', SCR_PLUGIN_FILE, true );
        }
    }
} 
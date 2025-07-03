<?php

namespace SimpleCouponRestrictions\Core;

/**
 * Plugin Activator Class
 */
class Activator {
    
    /**
     * Activate the plugin
     */
    public static function activate() {
        // Create database tables if needed
        self::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for tracking restricted customers
        $table_name = $wpdb->prefix . 'scr_restricted_customers';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) NOT NULL,
            coupon_code varchar(255) NOT NULL,
            order_id bigint(20) NOT NULL,
            restricted_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY customer_id (customer_id),
            KEY coupon_code (coupon_code),
            KEY order_id (order_id)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
    
    /**
     * Set default options
     */
    private static function set_default_options() {
        // Initialize empty restricted coupons array (only if doesn't exist)
        if ( false === get_option( 'scr_restricted_coupons' ) ) {
            add_option( 'scr_restricted_coupons', array() );
        }
        
        // Set plugin version
        update_option( 'scr_version', SCR_VERSION );
    }
} 
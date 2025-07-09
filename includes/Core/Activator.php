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
        
        // Check for database updates
        self::check_database_updates();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for tracking restricted customers (both registered and guest)
        $table_name = $wpdb->prefix . 'scr_restricted_customers';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) NULL,
            customer_email varchar(100) NOT NULL,
            customer_type enum('registered', 'guest') DEFAULT 'registered',
            coupon_code varchar(255) NOT NULL,
            order_id bigint(20) NOT NULL,
            restricted_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY customer_id (customer_id),
            KEY customer_email (customer_email),
            KEY customer_type (customer_type),
            KEY coupon_code (coupon_code),
            KEY order_id (order_id),
            UNIQUE KEY unique_customer_coupon (customer_email, coupon_code)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
    
    /**
     * Check for database updates
     */
    private static function check_database_updates() {
        $current_version = get_option( 'scr_db_version', '1.0.0' );
        
        // If we're updating from a version that didn't support guest customers
        if ( version_compare( $current_version, '1.1.0', '<' ) ) {
            self::update_database_to_1_1_0();
        }
        
        // Update database version
        update_option( 'scr_db_version', '1.1.1' );
    }
    
    /**
     * Update database to version 1.1.0 (add guest customer support)
     */
    private static function update_database_to_1_1_0() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'scr_restricted_customers';
        
        // Check if columns exist before adding them
        $columns = $wpdb->get_col( "DESCRIBE $table_name" );
        
        if ( ! in_array( 'customer_email', $columns ) ) {
            $wpdb->query( "ALTER TABLE $table_name ADD COLUMN customer_email varchar(100) NOT NULL DEFAULT ''" );
        }
        
        if ( ! in_array( 'customer_type', $columns ) ) {
            $wpdb->query( "ALTER TABLE $table_name ADD COLUMN customer_type enum('registered', 'guest') DEFAULT 'registered'" );
        }
        
        // Make customer_id nullable
        $wpdb->query( "ALTER TABLE $table_name MODIFY customer_id bigint(20) NULL" );
        
        // Add indexes
        $wpdb->query( "ALTER TABLE $table_name ADD INDEX customer_email (customer_email)" );
        $wpdb->query( "ALTER TABLE $table_name ADD INDEX customer_type (customer_type)" );
        
        // Try to add unique constraint (may fail if duplicates exist)
        $wpdb->query( "ALTER TABLE $table_name ADD UNIQUE KEY unique_customer_coupon (customer_email, coupon_code)" );
        
        // Populate email field for existing records
        $wpdb->query( "
            UPDATE $table_name r
            JOIN {$wpdb->users} u ON r.customer_id = u.ID
            SET r.customer_email = u.user_email
            WHERE r.customer_email = '' AND r.customer_id IS NOT NULL
        " );
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
        
        // Set database version
        if ( false === get_option( 'scr_db_version' ) ) {
            add_option( 'scr_db_version', '1.1.1' );
        }
    }
} 
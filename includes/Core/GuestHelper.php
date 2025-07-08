<?php

namespace SimpleCouponRestrictions\Core;

/**
 * Guest Helper Class
 * Manages guest customer identification and data storage
 */
class GuestHelper {
    
    /**
     * Get customer identifier (email) from order
     */
    public static function get_customer_identifier_from_order( $order ) {
        // Try to get billing email (HPOS compatible)
        if ( method_exists( $order, 'get_billing_email' ) ) {
            return $order->get_billing_email();
        }
        
        // Fallback for older WooCommerce versions
        $billing_email = $order->get_meta( '_billing_email' );
        if ( $billing_email ) {
            return $billing_email;
        }
        
        return '';
    }
    
    /**
     * Get customer type from order
     */
    public static function get_customer_type_from_order( $order ) {
        $customer_id = self::get_customer_id_from_order( $order );
        return $customer_id > 0 ? 'registered' : 'guest';
    }
    
    /**
     * Get customer ID from order (HPOS compatible)
     */
    public static function get_customer_id_from_order( $order ) {
        // Try different methods for HPOS compatibility
        if ( method_exists( $order, 'get_customer_id' ) ) {
            return $order->get_customer_id();
        }
        
        // Fallback for older WooCommerce versions
        if ( method_exists( $order, 'get_user_id' ) ) {
            return $order->get_user_id();
        }
        
        return 0;
    }
    
    /**
     * Get current customer identifier (email)
     */
    public static function get_current_customer_identifier() {
        // For registered users
        if ( is_user_logged_in() ) {
            $user = wp_get_current_user();
            return $user->user_email;
        }
        
        // For guest users, try to get from cart/checkout
        if ( WC()->cart && WC()->customer ) {
            $email = WC()->customer->get_billing_email();
            if ( $email ) {
                return $email;
            }
        }
        
        // Try to get from session
        if ( WC()->session ) {
            $customer_data = WC()->session->get( 'customer' );
            if ( isset( $customer_data['email'] ) ) {
                return $customer_data['email'];
            }
        }
        
        return '';
    }
    
    /**
     * Get current customer type
     */
    public static function get_current_customer_type() {
        return is_user_logged_in() ? 'registered' : 'guest';
    }
    
    /**
     * Store restricted coupon data for guest customer
     */
    public static function store_guest_restriction( $email, $coupon_codes, $order_id ) {
        // Store in session for immediate access (email-specific)
        if ( WC()->session ) {
            $session_key = 'scr_restricted_coupons_' . md5( $email );
            $existing_restricted = WC()->session->get( $session_key, array() );
            $updated_restricted = array_unique( array_merge( $existing_restricted, $coupon_codes ) );
            WC()->session->set( $session_key, $updated_restricted );
        }
        
        // Store in transient for longer persistence (24 hours)
        $transient_key = 'scr_guest_restrictions_' . md5( $email );
        $existing_restricted = get_transient( $transient_key );
        if ( ! is_array( $existing_restricted ) ) {
            $existing_restricted = array();
        }
        
        $updated_restricted = array_unique( array_merge( $existing_restricted, $coupon_codes ) );
        set_transient( $transient_key, $updated_restricted, 24 * HOUR_IN_SECONDS );
    }
    
    /**
     * Get restricted coupons for guest customer
     */
    public static function get_guest_restricted_coupons( $email ) {
        $restricted_coupons = array();
        
        // Try to get from session first (email-specific)
        if ( WC()->session ) {
            $session_key = 'scr_restricted_coupons_' . md5( $email );
            $session_restricted = WC()->session->get( $session_key, array() );
            if ( is_array( $session_restricted ) ) {
                $restricted_coupons = array_merge( $restricted_coupons, $session_restricted );
            }
        }
        
        // Get from transient
        $transient_key = 'scr_guest_restrictions_' . md5( $email );
        $transient_restricted = get_transient( $transient_key );
        if ( is_array( $transient_restricted ) ) {
            $restricted_coupons = array_merge( $restricted_coupons, $transient_restricted );
        }
        
        // Get from database
        global $wpdb;
        $table_name = $wpdb->prefix . 'scr_restricted_customers';
        $db_restricted = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT coupon_code FROM $table_name WHERE customer_email = %s",
            $email
        ) );
        
        if ( is_array( $db_restricted ) ) {
            $restricted_coupons = array_merge( $restricted_coupons, $db_restricted );
        }
        
        return array_unique( $restricted_coupons );
    }
    
    /**
     * Check if customer (guest or registered) is restricted
     */
    public static function is_customer_restricted( $email ) {
        $restricted_coupons = self::get_guest_restricted_coupons( $email );
        return ! empty( $restricted_coupons );
    }
    
    /**
     * Clean up expired guest restrictions
     */
    public static function cleanup_expired_guest_restrictions() {
        global $wpdb;
        
        // Clean up guest restrictions older than 30 days
        $table_name = $wpdb->prefix . 'scr_restricted_customers';
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM $table_name WHERE customer_type = 'guest' AND restricted_at < %s",
            date( 'Y-m-d H:i:s', strtotime( '-30 days' ) )
        ) );
    }
    
    /**
     * Clear old global session data (for migration from old version)
     */
    public static function clear_old_session_data() {
        if ( WC()->session ) {
            WC()->session->__unset( 'scr_restricted_coupons' );
        }
    }
    
    /**
     * Clear session data for specific email (utility method)
     */
    public static function clear_session_data_for_email( $email ) {
        if ( WC()->session ) {
            $session_key = 'scr_restricted_coupons_' . md5( $email );
            WC()->session->__unset( $session_key );
        }
    }
} 
<?php

namespace SimpleCouponRestrictions\Core;

/**
 * Coupon Tracker Class
 */
class CouponTracker {
    
    /**
     * Track coupons used in an order
     */
    public function track_order_coupons( $order ) {
        // Get restricted coupons
        $restricted_coupons = $this->get_restricted_coupons();
        
        if ( empty( $restricted_coupons ) ) {
            return;
        }
        
        // Get customer information (HPOS compatible)
        $customer_id = GuestHelper::get_customer_id_from_order( $order );
        $customer_email = GuestHelper::get_customer_identifier_from_order( $order );
        $customer_type = GuestHelper::get_customer_type_from_order( $order );
        
        if ( empty( $customer_email ) ) {
            return;
        }
        
        // Get used coupons (HPOS compatible)
        $used_coupons = $this->get_coupons_from_order( $order );
        
        if ( empty( $used_coupons ) ) {
            return;
        }
        
        // Check for restricted coupons
        $restricted_coupons_used = array();
        foreach ( $used_coupons as $coupon_code ) {
            if ( in_array( $coupon_code, $restricted_coupons ) ) {
                $restricted_coupons_used[] = $coupon_code;
            }
        }
        
        // Save to database
        if ( ! empty( $restricted_coupons_used ) ) {
            $this->save_restriction( $customer_id, $customer_email, $customer_type, $restricted_coupons_used, $order->get_id() );
        }
    }
    
    /**
     * Get restricted coupons from options
     */
    private function get_restricted_coupons() {
        return get_option( 'scr_restricted_coupons', array() );
    }
    
    /**
     * Get coupons from order (HPOS compatible)
     */
    private function get_coupons_from_order( $order ) {
        // Try different methods for HPOS compatibility
        if ( method_exists( $order, 'get_coupon_codes' ) ) {
            return $order->get_coupon_codes();
        }
        
        // Fallback for older WooCommerce versions
        if ( method_exists( $order, 'get_used_coupons' ) ) {
            return $order->get_used_coupons();
        }
        
        return array();
    }
    
    /**
     * Save restriction to database and handle both registered and guest customers
     */
    private function save_restriction( $customer_id, $customer_email, $customer_type, $coupon_codes, $order_id ) {
        global $wpdb;
        
        // Save to database
        $table_name = $wpdb->prefix . 'scr_restricted_customers';
        
        foreach ( $coupon_codes as $coupon_code ) {
            // Check if this combination already exists
            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM $table_name WHERE customer_email = %s AND coupon_code = %s",
                $customer_email,
                $coupon_code
            ) );
            
            // Only insert if it doesn't exist
            if ( ! $existing ) {
                $wpdb->insert(
                    $table_name,
                    array(
                        'customer_id' => $customer_id > 0 ? $customer_id : null,
                        'customer_email' => $customer_email,
                        'customer_type' => $customer_type,
                        'coupon_code' => $coupon_code,
                        'order_id' => $order_id,
                        'restricted_at' => current_time( 'mysql' )
                    ),
                    array( '%d', '%s', '%s', '%s', '%d', '%s' )
                );
            }
        }
        
        // Handle registered customers - save to user meta for quick access
        if ( $customer_type === 'registered' && $customer_id > 0 ) {
            $existing_restricted = get_user_meta( $customer_id, '_restricted_coupons_used', true );
            if ( ! is_array( $existing_restricted ) ) {
                $existing_restricted = array();
            }
            
            $updated_restricted = array_unique( array_merge( $existing_restricted, $coupon_codes ) );
            update_user_meta( $customer_id, '_restricted_coupons_used', $updated_restricted );
        }
        
        // Handle guest customers - use GuestHelper to store in session and transient
        if ( $customer_type === 'guest' ) {
            GuestHelper::store_guest_restriction( $customer_email, $coupon_codes, $order_id );
        }
        
        // Log for debugging
        error_log( "SCR: {$customer_type} customer {$customer_email} used restricted coupons: " . implode( ', ', $coupon_codes ) );
    }
    
    /**
     * Get restricted coupons for any customer (registered or guest)
     */
    public function get_customer_restricted_coupons( $customer_email, $customer_id = 0 ) {
        $restricted_coupons = array();
        
        // For registered customers, try user meta first
        if ( $customer_id > 0 ) {
            $user_meta_restricted = get_user_meta( $customer_id, '_restricted_coupons_used', true );
            if ( is_array( $user_meta_restricted ) ) {
                $restricted_coupons = array_merge( $restricted_coupons, $user_meta_restricted );
            }
        }
        
        // Get from database (works for both registered and guest)
        global $wpdb;
        $table_name = $wpdb->prefix . 'scr_restricted_customers';
        $db_restricted = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT coupon_code FROM $table_name WHERE customer_email = %s",
            $customer_email
        ) );
        
        if ( is_array( $db_restricted ) ) {
            $restricted_coupons = array_merge( $restricted_coupons, $db_restricted );
        }
        
        // For guest customers, also check session and transient
        if ( $customer_id === 0 ) {
            $guest_restricted = GuestHelper::get_guest_restricted_coupons( $customer_email );
            if ( is_array( $guest_restricted ) ) {
                $restricted_coupons = array_merge( $restricted_coupons, $guest_restricted );
            }
        }
        
        return array_unique( $restricted_coupons );
    }
    
    /**
     * Check if customer is restricted (works for both registered and guest)
     */
    public function is_customer_restricted( $customer_email, $customer_id = 0 ) {
        $restricted_coupons = $this->get_customer_restricted_coupons( $customer_email, $customer_id );
        return ! empty( $restricted_coupons );
    }
} 
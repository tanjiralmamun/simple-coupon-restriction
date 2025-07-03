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
        
        // Get customer ID (HPOS compatible)
        $customer_id = $this->get_customer_id_from_order( $order );
        
        if ( ! $customer_id ) {
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
        
        // Save to database and user meta
        if ( ! empty( $restricted_coupons_used ) ) {
            $this->save_restriction( $customer_id, $restricted_coupons_used, $order->get_id() );
        }
    }
    
    /**
     * Get restricted coupons from options
     */
    private function get_restricted_coupons() {
        return get_option( 'scr_restricted_coupons', array() );
    }
    
    /**
     * Get customer ID from order (HPOS compatible)
     */
    private function get_customer_id_from_order( $order ) {
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
     * Save restriction to database and user meta
     */
    private function save_restriction( $customer_id, $coupon_codes, $order_id ) {
        global $wpdb;
        
        // Save to database
        $table_name = $wpdb->prefix . 'scr_restricted_customers';
        
        foreach ( $coupon_codes as $coupon_code ) {
            $wpdb->insert(
                $table_name,
                array(
                    'customer_id' => $customer_id,
                    'coupon_code' => $coupon_code,
                    'order_id' => $order_id,
                    'restricted_at' => current_time( 'mysql' )
                ),
                array( '%d', '%s', '%d', '%s' )
            );
        }
        
        // Save to user meta for quick access
        $existing_restricted = get_user_meta( $customer_id, '_restricted_coupons_used', true );
        if ( ! is_array( $existing_restricted ) ) {
            $existing_restricted = array();
        }
        
        $updated_restricted = array_unique( array_merge( $existing_restricted, $coupon_codes ) );
        update_user_meta( $customer_id, '_restricted_coupons_used', $updated_restricted );
        
        // Log for debugging
        error_log( "SCR: Customer {$customer_id} used restricted coupons: " . implode( ', ', $coupon_codes ) );
    }
} 
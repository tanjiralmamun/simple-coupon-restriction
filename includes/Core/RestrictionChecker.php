<?php

namespace SimpleCouponRestrictions\Core;

/**
 * Restriction Checker Class
 */
class RestrictionChecker {
    
    /**
     * Check if customer is restricted from using coupons
     */
    public function check_customer_restriction( $is_valid, $coupon ) {
        // Don't proceed if we're not in a cart/checkout environment
        if ( ! WC()->cart ) {
            return $is_valid;
        }
        
        // Get customer identifier (email)
        $customer_email = GuestHelper::get_current_customer_identifier();
        
        if ( empty( $customer_email ) ) {
            return $is_valid;
        }
        
        // Get customer ID (0 for guest customers)
        $customer_id = is_user_logged_in() ? get_current_user_id() : 0;
        
        // Get current coupon code
        $current_coupon_code = $coupon->get_code();
        
        // Get list of restricted coupons
        $restricted_coupons = get_option( 'scr_restricted_coupons', array() );
        
        // If the current coupon is not restricted, allow it
        if ( ! in_array( $current_coupon_code, $restricted_coupons ) ) {
            return $is_valid;
        }
        
        // If the current coupon IS restricted, check if customer has used restricted coupons before
        $restricted_coupons_used = $this->get_customer_restricted_coupons( $customer_email, $customer_id );
        
        // If customer has used restricted coupons before, block this restricted coupon
        if ( ! empty( $restricted_coupons_used ) && is_array( $restricted_coupons_used ) ) {
            $customer_type = is_user_logged_in() ? 'registered' : 'guest';
            
            // Block the restricted coupon and show error message
            $error_message = sprintf(
                __( 'You cannot use the restricted coupon "%s" because you have previously used restricted coupons (%s). You can still use non-restricted coupons.', 'simple-coupon-restrictions' ),
                $current_coupon_code,
                implode( ', ', $restricted_coupons_used )
            );
            
            // Log for debugging
            error_log( "SCR: Blocked restricted coupon {$current_coupon_code} for {$customer_type} customer {$customer_email} who previously used: " . implode( ', ', $restricted_coupons_used ) );
            
            throw new \Exception( $error_message );
        }
        
        return $is_valid;
    }
    
    /**
     * Check if a customer is restricted (works for both registered and guest)
     */
    public function is_customer_restricted( $customer_email, $customer_id = 0 ) {
        $restricted_coupons_used = $this->get_customer_restricted_coupons( $customer_email, $customer_id );
        return ! empty( $restricted_coupons_used ) && is_array( $restricted_coupons_used );
    }
    
    /**
     * Get restricted coupons for a customer (works for both registered and guest)
     */
    public function get_customer_restricted_coupons( $customer_email, $customer_id = 0 ) {
        $restricted_coupons = array();
        
        // For registered customers, try user meta first for quick access
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
     * Get restricted coupons for current customer (backward compatibility)
     */
    public function get_current_customer_restricted_coupons() {
        $customer_email = GuestHelper::get_current_customer_identifier();
        $customer_id = is_user_logged_in() ? get_current_user_id() : 0;
        
        return $this->get_customer_restricted_coupons( $customer_email, $customer_id );
    }
    
    /**
     * Check if current customer is restricted (backward compatibility)
     */
    public function is_current_customer_restricted() {
        $customer_email = GuestHelper::get_current_customer_identifier();
        $customer_id = is_user_logged_in() ? get_current_user_id() : 0;
        
        return $this->is_customer_restricted( $customer_email, $customer_id );
    }
} 
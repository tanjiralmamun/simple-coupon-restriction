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
        
        // Only check for logged-in customers
        if ( ! is_user_logged_in() ) {
            return $is_valid;
        }
        
        $customer_id = get_current_user_id();
        $restricted_coupons_used = get_user_meta( $customer_id, '_restricted_coupons_used', true );
        
        // If customer has used restricted coupons before, block all coupons
        if ( ! empty( $restricted_coupons_used ) && is_array( $restricted_coupons_used ) ) {
            $current_coupon_code = $coupon->get_code();
            
            // Block the coupon and show error message
            $error_message = sprintf(
                __( 'You cannot use coupon "%s" because you have previously used restricted coupons (%s).', 'simple-coupon-restrictions' ),
                $current_coupon_code,
                implode( ', ', $restricted_coupons_used )
            );
            
            throw new \Exception( $error_message );
        }
        
        return $is_valid;
    }
    
    /**
     * Check if a customer is restricted
     */
    public function is_customer_restricted( $customer_id ) {
        $restricted_coupons_used = get_user_meta( $customer_id, '_restricted_coupons_used', true );
        return ! empty( $restricted_coupons_used ) && is_array( $restricted_coupons_used );
    }
    
    /**
     * Get restricted coupons for a customer
     */
    public function get_customer_restricted_coupons( $customer_id ) {
        $restricted_coupons_used = get_user_meta( $customer_id, '_restricted_coupons_used', true );
        return is_array( $restricted_coupons_used ) ? $restricted_coupons_used : array();
    }
} 
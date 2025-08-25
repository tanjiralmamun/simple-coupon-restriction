<?php

namespace SimpleCouponRestrictions\Core;

/**
 * Prefix Restriction Class
 * 
 * Handles prefix-based coupon restrictions
 */
class PrefixRestriction {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Hook prefix validation
        add_filter( 'woocommerce_coupon_is_valid', array( $this, 'validate_prefix_exclusions' ), 20, 2 );
    }
    
    /**
     * Validate coupon against prefix exclusions
     * 
     * @param bool $valid Current validation status
     * @param \WC_Coupon $coupon Coupon object
     * @return bool
     * @throws \Exception
     */
    public function validate_prefix_exclusions( $valid, $coupon ) {
        if ( ! $valid || ! WC()->cart ) {
            return $valid;
        }
        
        // Get all applied coupons in cart
        $applied_coupons = WC()->cart->get_applied_coupons();
        
        if ( empty( $applied_coupons ) ) {
            return $valid;
        }
        
        $current_coupon_code = $coupon->get_code();
        
        // Check if current coupon is restricted by any applied coupon's prefix restrictions
        $this->check_applied_coupon_restrictions( $current_coupon_code, $applied_coupons );
        
        // Check if current coupon has prefix restrictions that conflict with applied coupons
        $this->check_current_coupon_restrictions( $coupon, $applied_coupons );
        
        return $valid;
    }
    
    /**
     * Check if current coupon is restricted by applied coupons
     * 
     * @param string $current_coupon_code Current coupon code
     * @param array $applied_coupons Applied coupon codes
     * @throws \Exception
     */
    private function check_applied_coupon_restrictions( $current_coupon_code, $applied_coupons ) {
        foreach ( $applied_coupons as $applied_code ) {
            $applied_coupon_id = wc_get_coupon_id_by_code( $applied_code );
            $excluded_prefixes = $this->get_excluded_prefixes( $applied_coupon_id );
            
            if ( ! empty( $excluded_prefixes ) ) {
                foreach ( $excluded_prefixes as $prefix ) {
                    if ( $this->coupon_matches_prefix( $current_coupon_code, $prefix ) ) {
                        $error_message = sprintf(
                            __( 'The coupon "%s" cannot be used because it conflicts with an already applied coupon that restricts coupons starting with "%s".', 'simple-coupon-restrictions' ),
                            $current_coupon_code,
                            $prefix
                        );
                        throw new \Exception( $error_message );
                    }
                }
            }
        }
    }
    
    /**
     * Check if current coupon restrictions conflict with applied coupons
     * 
     * @param \WC_Coupon $coupon Current coupon object
     * @param array $applied_coupons Applied coupon codes
     * @throws \Exception
     */
    private function check_current_coupon_restrictions( $coupon, $applied_coupons ) {
        $current_coupon_code = $coupon->get_code();
        $current_coupon_id = $coupon->get_id();
        $current_excluded_prefixes = $this->get_excluded_prefixes( $current_coupon_id );
        
        if ( ! empty( $current_excluded_prefixes ) ) {
            foreach ( $applied_coupons as $applied_code ) {
                foreach ( $current_excluded_prefixes as $prefix ) {
                    if ( $this->coupon_matches_prefix( $applied_code, $prefix ) ) {
                        $error_message = sprintf(
                            __( 'The coupon "%s" cannot be used because it restricts coupons starting with "%s", and "%s" is already applied.', 'simple-coupon-restrictions' ),
                            $current_coupon_code,
                            $prefix,
                            $applied_code
                        );
                        throw new \Exception( $error_message );
                    }
                }
            }
        }
    }
    
    /**
     * Get excluded prefixes for a coupon
     * 
     * @param int $coupon_id Coupon ID
     * @return array
     */
    public function get_excluded_prefixes( $coupon_id ) {
        $excluded_prefixes = get_post_meta( $coupon_id, '_scr_excluded_coupon_prefixes', true );
        
        if ( ! is_array( $excluded_prefixes ) ) {
            return array();
        }
        
        // Filter out empty prefixes
        return array_filter( $excluded_prefixes, 'strlen' );
    }
    
    /**
     * Check if coupon code matches a prefix (case-insensitive)
     * 
     * @param string $coupon_code Coupon code to check
     * @param string $prefix Prefix to match against
     * @return bool
     */
    private function coupon_matches_prefix( $coupon_code, $prefix ) {
        if ( empty( $prefix ) || empty( $coupon_code ) ) {
            return false;
        }
        
        return stripos( $coupon_code, $prefix ) === 0;
    }
    
    /**
     * Save excluded prefixes for a coupon
     * 
     * @param int $coupon_id Coupon ID
     * @param array|string $prefixes Prefixes to save
     */
    public function save_excluded_prefixes( $coupon_id, $prefixes ) {
        if ( is_string( $prefixes ) ) {
            // Convert comma-separated string to array and trim whitespace
            $prefixes_array = array_map( 'trim', explode( ',', $prefixes ) );
            $prefixes_array = array_filter( $prefixes_array, 'strlen' ); // Remove empty strings
        } else {
            $prefixes_array = is_array( $prefixes ) ? $prefixes : array();
        }
        
        update_post_meta( $coupon_id, '_scr_excluded_coupon_prefixes', $prefixes_array );
    }
    
    /**
     * Delete excluded prefixes for a coupon
     * 
     * @param int $coupon_id Coupon ID
     */
    public function delete_excluded_prefixes( $coupon_id ) {
        delete_post_meta( $coupon_id, '_scr_excluded_coupon_prefixes' );
    }
}

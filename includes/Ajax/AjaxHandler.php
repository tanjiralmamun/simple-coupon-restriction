<?php

namespace SimpleCouponRestrictions\Ajax;

/**
 * AJAX Handler Class
 */
class AjaxHandler {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'wp_ajax_scr_search_coupons', array( $this, 'search_coupons' ) );
        add_action( 'wp_ajax_scr_load_customers_page', array( $this, 'load_customers_page' ) );
    }
    
    /**
     * Search coupons via AJAX
     */
    public function search_coupons() {
        // Check nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'scr_admin_nonce' ) ) {
            wp_die( 'Security check failed' );
        }
        
        // Check permissions
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Insufficient permissions' );
        }
        
        $search_term = sanitize_text_field( $_POST['term'] );
        
        if ( empty( $search_term ) ) {
            wp_send_json_error( array( 'message' => 'No search term provided' ) );
        }
        
        $coupons = $this->get_coupons_by_search( $search_term );
        
        wp_send_json_success( $coupons );
    }
    
    /**
     * Get coupons by search term (code and title)
     */
    private function get_coupons_by_search( $search_term ) {
        global $wpdb;
        
        // Search in both post_title (coupon code) and post_excerpt (coupon description)
        $query = $wpdb->prepare( "
            SELECT ID, post_title, post_excerpt
            FROM {$wpdb->posts}
            WHERE post_type = 'shop_coupon'
            AND post_status = 'publish'
            AND (
                post_title LIKE %s
                OR post_excerpt LIKE %s
            )
            ORDER BY post_title ASC
            LIMIT 20
        ", '%' . $wpdb->esc_like( $search_term ) . '%', '%' . $wpdb->esc_like( $search_term ) . '%' );
        
        $results = $wpdb->get_results( $query );
        
        $coupons = array();
        
        foreach ( $results as $result ) {
            $coupons[] = array(
                'id' => $result->ID,
                'code' => $result->post_title,
                'title' => ! empty( $result->post_excerpt ) ? $result->post_excerpt : $result->post_title,
                'label' => $result->post_title . ( ! empty( $result->post_excerpt ) ? ' - ' . $result->post_excerpt : '' )
            );
        }
        
        return $coupons;
    }
    
    /**
     * Load customers page via AJAX
     */
    public function load_customers_page() {
        // Check nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'scr_admin_nonce' ) ) {
            wp_die( 'Security check failed' );
        }
        
        // Check permissions
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Insufficient permissions' );
        }
        
        $page = intval( $_POST['page'] );
        
        if ( $page < 1 ) {
            wp_send_json_error( array( 'message' => 'Invalid page number' ) );
        }
        
        // Create AdminView instance and render the table
        $admin_view = new \SimpleCouponRestrictions\Admin\AdminView();
        
        ob_start();
        $admin_view->render_customers_table( null, $page );
        $html = ob_get_clean();
        
        wp_send_json_success( array( 'html' => $html ) );
    }
} 
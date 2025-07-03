<?php

namespace SimpleCouponRestrictions\Admin;

/**
 * Admin Controller Class
 */
class AdminController {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
        add_action( 'admin_init', array( $this, 'handle_admin_actions' ) );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'Coupon Restrictions', 'simple-coupon-restrictions' ),
            __( 'Coupon Restrictions', 'simple-coupon-restrictions' ),
            'manage_woocommerce',
            'simple-coupon-restrictions',
            array( $this, 'render_admin_page' )
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( 'woocommerce_page_simple-coupon-restrictions' !== $hook ) {
            return;
        }
        
        // Enqueue SweetAlert2 (local files)
        wp_enqueue_style(
            'sweetalert2-css',
            SCR_PLUGIN_URL . 'assets/css/sweetalert2.min.css',
            array(),
            '11.22.2'
        );
        
        wp_enqueue_script(
            'sweetalert2',
            SCR_PLUGIN_URL . 'assets/js/sweetalert2.min.js',
            array(),
            '11.22.2',
            true
        );
        
        wp_enqueue_script(
            'scr-admin-js',
            SCR_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery', 'jquery-ui-autocomplete', 'sweetalert2' ),
            SCR_VERSION,
            true
        );
        
        wp_enqueue_style(
            'scr-admin-css',
            SCR_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SCR_VERSION
        );
        
        // Localize script for AJAX
        wp_localize_script( 'scr-admin-js', 'scr_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'scr_admin_nonce' ),
            'strings' => array(
                'search_placeholder' => __( 'Search coupons...', 'simple-coupon-restrictions' ),
                'no_results' => __( 'No coupons found', 'simple-coupon-restrictions' ),
                'confirm_reset' => __( 'Are you sure you want to reset restrictions for this customer?', 'simple-coupon-restrictions' ),
                'confirm_remove' => __( 'Are you sure you want to remove this coupon from restricted list?', 'simple-coupon-restrictions' )
            )
        ) );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $view = new AdminView();
        $view->render();
    }
    
    /**
     * Show admin notices
     */
    public function show_admin_notices() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }
        
        // Show success message after actions
        if ( isset( $_GET['scr_action'] ) && $_GET['scr_action'] === 'success' ) {
            $message = isset( $_GET['scr_message'] ) ? sanitize_text_field( $_GET['scr_message'] ) : '';
            if ( $message ) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>' . esc_html( $message ) . '</p>';
                echo '</div>';
            }
        }
        
        // Show restricted customer notice on user edit page
        if ( isset( $_GET['user_id'] ) ) {
            $user_id = intval( $_GET['user_id'] );
            $restriction_checker = new \SimpleCouponRestrictions\Core\RestrictionChecker();
            
            if ( $restriction_checker->is_customer_restricted( $user_id ) ) {
                $restricted_coupons = $restriction_checker->get_customer_restricted_coupons( $user_id );
                
                echo '<div class="notice notice-warning">';
                echo '<p><strong>' . __( 'Coupon Restriction:', 'simple-coupon-restrictions' ) . '</strong> ';
                echo __( 'This customer has used restricted coupons:', 'simple-coupon-restrictions' ) . ' <strong>' . implode( ', ', $restricted_coupons ) . '</strong></p>';
                echo '<p>' . __( 'They are blocked from using any coupons in future orders.', 'simple-coupon-restrictions' ) . '</p>';
                echo '<p><a href="' . admin_url( 'admin.php?page=simple-coupon-restrictions&action=reset_customer&customer_id=' . $user_id . '&_wpnonce=' . wp_create_nonce( 'scr_reset_customer_' . $user_id ) ) . '" class="button button-secondary">' . __( 'Reset Restrictions', 'simple-coupon-restrictions' ) . '</a></p>';
                echo '</div>';
            }
        }
    }
    
    /**
     * Handle admin actions
     */
    public function handle_admin_actions() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }
        
        // Handle settings save
        if ( isset( $_POST['scr_save_settings'] ) && wp_verify_nonce( $_POST['scr_nonce'], 'scr_save_settings' ) ) {
            $this->save_settings();
        }
        
        // Handle customer reset
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'reset_customer' && isset( $_GET['customer_id'] ) ) {
            $this->reset_customer_restrictions();
        }
        
        // Handle coupon removal
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'remove_coupon' && isset( $_GET['coupon'] ) ) {
            $this->remove_restricted_coupon();
        }
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        $restricted_coupons = array();
        
        if ( isset( $_POST['scr_restricted_coupons'] ) && is_array( $_POST['scr_restricted_coupons'] ) ) {
            foreach ( $_POST['scr_restricted_coupons'] as $coupon ) {
                $coupon = sanitize_text_field( $coupon );
                if ( ! empty( $coupon ) ) {
                    $restricted_coupons[] = $coupon;
                }
            }
        }
        
        update_option( 'scr_restricted_coupons', $restricted_coupons );
        
        $this->redirect_with_message( __( 'Settings saved successfully!', 'simple-coupon-restrictions' ) );
    }
    
    /**
     * Reset customer restrictions
     */
    private function reset_customer_restrictions() {
        $customer_id = intval( $_GET['customer_id'] );
        $nonce = $_GET['_wpnonce'];
        
        if ( ! wp_verify_nonce( $nonce, 'scr_reset_customer_' . $customer_id ) ) {
            wp_die( __( 'Security check failed', 'simple-coupon-restrictions' ) );
        }
        
        delete_user_meta( $customer_id, '_restricted_coupons_used' );
        
        // Also remove from database
        global $wpdb;
        $table_name = $wpdb->prefix . 'scr_restricted_customers';
        $wpdb->delete( $table_name, array( 'customer_id' => $customer_id ), array( '%d' ) );
        
        $this->redirect_with_message( __( 'Customer restrictions have been reset successfully!', 'simple-coupon-restrictions' ) );
    }
    
    /**
     * Remove restricted coupon
     */
    private function remove_restricted_coupon() {
        $coupon_code = sanitize_text_field( $_GET['coupon'] );
        $nonce = $_GET['_wpnonce'];
        
        if ( ! wp_verify_nonce( $nonce, 'scr_remove_coupon_' . $coupon_code ) ) {
            wp_die( __( 'Security check failed', 'simple-coupon-restrictions' ) );
        }
        
        $restricted_coupons = get_option( 'scr_restricted_coupons', array() );
        $restricted_coupons = array_diff( $restricted_coupons, array( $coupon_code ) );
        update_option( 'scr_restricted_coupons', $restricted_coupons );
        
        $this->redirect_with_message( sprintf( __( 'Coupon "%s" has been removed from restricted list!', 'simple-coupon-restrictions' ), $coupon_code ) );
    }
    
    /**
     * Redirect with success message
     */
    private function redirect_with_message( $message ) {
        $redirect_url = add_query_arg( 
            array( 
                'page' => 'simple-coupon-restrictions',
                'scr_action' => 'success',
                'scr_message' => urlencode( $message )
            ), 
            admin_url( 'admin.php' ) 
        );
        
        wp_redirect( $redirect_url );
        exit;
    }
} 
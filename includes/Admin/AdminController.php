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
            $user = get_user_by( 'id', $user_id );
            
            if ( $user ) {
                $restriction_checker = new \SimpleCouponRestrictions\Core\RestrictionChecker();
                
                if ( $restriction_checker->is_customer_restricted( $user->user_email, $user_id ) ) {
                    $restricted_coupons = $restriction_checker->get_customer_restricted_coupons( $user->user_email, $user_id );
                    
                    echo '<div class="notice notice-warning">';
                    echo '<p><strong>' . __( 'Coupon Restriction:', 'simple-coupon-restrictions' ) . '</strong> ';
                    echo __( 'This customer has used restricted coupons:', 'simple-coupon-restrictions' ) . ' <strong>' . implode( ', ', $restricted_coupons ) . '</strong></p>';
                    echo '<p>' . __( 'They are blocked from using any coupons in future orders.', 'simple-coupon-restrictions' ) . '</p>';
                    echo '<p><a href="' . admin_url( 'admin.php?page=simple-coupon-restrictions&action=reset_customer&customer_id=' . $user_id . '&customer_email=' . urlencode( $user->user_email ) . '&_wpnonce=' . wp_create_nonce( 'scr_reset_customer_' . $user_id ) ) . '" class="button button-secondary">' . __( 'Reset Restrictions', 'simple-coupon-restrictions' ) . '</a></p>';
                    echo '</div>';
                }
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
        
        // Handle processing existing orders
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'process_existing_orders' ) {
            $this->process_existing_orders();
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
        $customer_email = isset( $_GET['customer_email'] ) ? sanitize_email( $_GET['customer_email'] ) : '';
        $nonce = $_GET['_wpnonce'];
        
        // Handle nonce verification for both registered and guest customers
        if ( $customer_id > 0 ) {
            if ( ! wp_verify_nonce( $nonce, 'scr_reset_customer_' . $customer_id ) ) {
                wp_die( __( 'Security check failed', 'simple-coupon-restrictions' ) );
            }
        } else {
            if ( ! wp_verify_nonce( $nonce, 'scr_reset_customer_0' ) ) {
                wp_die( __( 'Security check failed', 'simple-coupon-restrictions' ) );
            }
        }
        
        // For registered customers, remove user meta
        if ( $customer_id > 0 ) {
            delete_user_meta( $customer_id, '_restricted_coupons_used' );
        }
        
        // Remove from database (works for both registered and guest)
        global $wpdb;
        $table_name = $wpdb->prefix . 'scr_restricted_customers';
        
        if ( $customer_email ) {
            $wpdb->delete( $table_name, array( 'customer_email' => $customer_email ), array( '%s' ) );
            
            // For guest customers, also clear session and transient data
            if ( $customer_id === 0 ) {
                $transient_key = 'scr_guest_restrictions_' . md5( $customer_email );
                delete_transient( $transient_key );
                
                // Clear email-specific session data if current session exists
                if ( WC()->session ) {
                    $session_key = 'scr_restricted_coupons_' . md5( $customer_email );
                    WC()->session->__unset( $session_key );
                    
                    // Also clear the old global session key for backward compatibility
                    WC()->session->__unset( 'scr_restricted_coupons' );
                }
            }
        } else {
            // Fallback to customer_id only (for backward compatibility)
            $wpdb->delete( $table_name, array( 'customer_id' => $customer_id ), array( '%d' ) );
        }
        
        $customer_type = $customer_id > 0 ? 'registered' : 'guest';
        $this->redirect_with_message( sprintf( 
            __( '%s customer restrictions have been reset successfully!', 'simple-coupon-restrictions' ),
            ucfirst( $customer_type )
        ) );
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
                'scr_action' => 'success', 
                'scr_message' => urlencode( $message ) 
            ), 
            admin_url( 'admin.php?page=simple-coupon-restrictions' ) 
        );
        wp_redirect( $redirect_url );
        exit;
    }
    
    /**
     * Process existing orders to track guest customers
     */
    private function process_existing_orders() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'scr_process_existing_orders' ) ) {
            wp_die( __( 'Security check failed', 'simple-coupon-restrictions' ) );
        }
        
        // Get restricted coupons
        $restricted_coupons = get_option( 'scr_restricted_coupons', array() );
        
        if ( empty( $restricted_coupons ) ) {
            $this->redirect_with_message( __( 'No restricted coupons configured.', 'simple-coupon-restrictions' ) );
            return;
        }
        
        // Get orders that used restricted coupons
        $orders = wc_get_orders( array(
            'limit' => -1,
            'status' => array( 'completed', 'processing' ),
            'meta_query' => array(
                array(
                    'key' => '_coupon_lines',
                    'compare' => 'EXISTS'
                )
            )
        ) );
        
        $processed_count = 0;
        $tracker = new \SimpleCouponRestrictions\Core\CouponTracker();
        
        foreach ( $orders as $order ) {
            $used_coupons = $order->get_coupon_codes();
            
            // Check if this order used any restricted coupons
            $has_restricted_coupon = false;
            foreach ( $used_coupons as $coupon_code ) {
                if ( in_array( $coupon_code, $restricted_coupons ) ) {
                    $has_restricted_coupon = true;
                    break;
                }
            }
            
            if ( $has_restricted_coupon ) {
                // Check if this order is already tracked
                global $wpdb;
                $table_name = $wpdb->prefix . 'scr_restricted_customers';
                $existing = $wpdb->get_var( $wpdb->prepare(
                    "SELECT id FROM $table_name WHERE order_id = %d",
                    $order->get_id()
                ) );
                
                if ( ! $existing ) {
                    $tracker->track_order_coupons( $order );
                    $processed_count++;
                }
            }
        }
        
        $this->redirect_with_message( sprintf( 
            __( 'Successfully processed %d existing orders with restricted coupons.', 'simple-coupon-restrictions' ),
            $processed_count
        ) );
    }
} 
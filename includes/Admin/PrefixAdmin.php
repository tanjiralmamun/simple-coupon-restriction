<?php

namespace SimpleCouponRestrictions\Admin;

use SimpleCouponRestrictions\Core\PrefixRestriction;

/**
 * Prefix Admin Class
 * 
 * Handles admin interface for prefix-based coupon restrictions
 */
class PrefixAdmin {
    
    /**
     * PrefixRestriction instance
     * 
     * @var PrefixRestriction
     */
    private $prefix_restriction;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->prefix_restriction = new PrefixRestriction();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Admin hooks
        add_action( 'woocommerce_coupon_options_usage_restriction', array( $this, 'add_prefix_exclusion_field' ), 15 );
        add_action( 'woocommerce_coupon_options_save', array( $this, 'save_prefix_exclusion_field' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }
    
    /**
     * Add custom coupon prefix exclusion field
     */
    public function add_prefix_exclusion_field() {
        global $post;
        
        if ( ! $post || $post->post_type !== 'shop_coupon' ) {
            return;
        }
        
        $excluded_prefixes = $this->prefix_restriction->get_excluded_prefixes( $post->ID );
        $excluded_prefixes_string = ! empty( $excluded_prefixes ) ? implode( ', ', $excluded_prefixes ) : '';
        
        $this->render_prefix_field( $excluded_prefixes_string );
    }
    
    /**
     * Render the prefix exclusion field
     * 
     * @param string $value Current field value
     */
    private function render_prefix_field( $value ) {
        ?>
        <div class="options_group">
            <p class="form-field scr_excluded_coupon_prefixes_field">
                <label for="scr_excluded_coupon_prefixes">
                    <?php _e( 'Exclude coupons with prefixes', 'simple-coupon-restrictions' ); ?>
                    <?php echo wc_help_tip( __( 'Enter comma-separated prefixes. This coupon cannot be used with coupons that start with these prefixes (e.g., "exchange" will block "exchange45645fd").', 'simple-coupon-restrictions' ) ); ?>
                </label>
                <input 
                    type="text" 
                    class="widefat" 
                    name="scr_excluded_coupon_prefixes" 
                    id="scr_excluded_coupon_prefixes" 
                    value="<?php echo esc_attr( $value ); ?>" 
                    placeholder="<?php _e( 'e.g., exchange, promo, discount', 'simple-coupon-restrictions' ); ?>" 
                />
            </p>
        </div>
        <?php
    }
    
    /**
     * Save custom coupon prefix exclusion field
     * 
     * @param int $coupon_id Coupon ID
     */
    public function save_prefix_exclusion_field( $coupon_id ) {
        // Verify nonce for security
        if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( $_POST['woocommerce_meta_nonce'], 'woocommerce_save_data' ) ) {
            return;
        }
        
        // Check user permissions
        if ( ! current_user_can( 'edit_post', $coupon_id ) ) {
            return;
        }
        
        if ( isset( $_POST['scr_excluded_coupon_prefixes'] ) ) {
            $prefixes = sanitize_text_field( $_POST['scr_excluded_coupon_prefixes'] );
            $this->prefix_restriction->save_excluded_prefixes( $coupon_id, $prefixes );
        } else {
            // If field is not set, delete the meta
            $this->prefix_restriction->delete_excluded_prefixes( $coupon_id );
        }
    }
    
    /**
     * Enqueue admin assets (CSS and JS)
     */
    public function enqueue_admin_assets( $hook ) {
        // Only load on coupon edit pages
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
            return;
        }
        
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'shop_coupon' ) {
            return;
        }
        
        // Enqueue CSS (use minified version in production)
        $css_file = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? 'prefix-admin.css' : 'prefix-admin.min.css';
        wp_enqueue_style(
            'scr-prefix-admin',
            SCR_PLUGIN_URL . 'assets/css/' . $css_file,
            array(),
            SCR_VERSION,
            'all'
        );
        
        // Add RTL support
        wp_style_add_data( 'scr-prefix-admin', 'rtl', 'replace' );
        if ( is_rtl() ) {
            wp_enqueue_style(
                'scr-prefix-admin-rtl',
                SCR_PLUGIN_URL . 'assets/css/prefix-admin-rtl.css',
                array( 'scr-prefix-admin' ),
                SCR_VERSION,
                'all'
            );
        }
        
        // Enqueue JavaScript (use minified version in production)
        $js_file = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? 'prefix-admin.js' : 'prefix-admin.js';
        wp_enqueue_script(
            'scr-prefix-admin',
            SCR_PLUGIN_URL . 'assets/js/' . $js_file,
            array( 'jquery', 'wc-admin-meta-boxes' ),
            SCR_VERSION,
            true
        );
        
        // Add script attributes for modern browsers
        wp_script_add_data( 'scr-prefix-admin', 'async', false );
        wp_script_add_data( 'scr-prefix-admin', 'defer', false );
        
        // Localize script with translatable strings and settings
        wp_localize_script( 'scr-prefix-admin', 'scrPrefixAdmin', array(
            'strings' => array(
                'invalidPrefix' => __( 'Invalid prefix format. Use 2-20 characters: letters, numbers, hyphens only.', 'simple-coupon-restrictions' ),
                'duplicateRemoved' => __( 'Duplicate prefixes have been removed.', 'simple-coupon-restrictions' ),
                'prefixesValid' => __( 'Prefixes are valid.', 'simple-coupon-restrictions' ),
                'emptyPrefixes' => __( 'Please enter at least one valid prefix.', 'simple-coupon-restrictions' ),
                'validationFailed' => __( 'Please fix the prefix restrictions before saving.', 'simple-coupon-restrictions' ),
            ),
            'settings' => array(
                'minLength' => 2,
                'maxLength' => 20,
                'allowedChars' => 'a-zA-Z0-9-',
                'caseSensitive' => false,
            ),
            'nonce' => wp_create_nonce( 'scr_prefix_admin' ),
        ) );
    }
    
    /**
     * Get prefix restrictions for display in admin
     * 
     * @param int $coupon_id Coupon ID
     * @return array
     */
    public function get_coupon_prefix_restrictions( $coupon_id ) {
        return $this->prefix_restriction->get_excluded_prefixes( $coupon_id );
    }
    
    /**
     * Add prefix information to coupon list table (optional enhancement)
     * 
     * @param array $columns Existing columns
     * @return array
     */
    public function add_prefix_column( $columns ) {
        $columns['prefix_restrictions'] = __( 'Prefix Restrictions', 'simple-coupon-restrictions' );
        return $columns;
    }
    
    /**
     * Display prefix information in coupon list table (optional enhancement)
     * 
     * @param string $column Column name
     * @param int $post_id Post ID
     */
    public function display_prefix_column( $column, $post_id ) {
        if ( $column === 'prefix_restrictions' ) {
            $prefixes = $this->prefix_restriction->get_excluded_prefixes( $post_id );
            if ( ! empty( $prefixes ) ) {
                echo '<span class="scr-prefix-restrictions">' . esc_html( implode( ', ', $prefixes ) ) . '</span>';
            } else {
                echo '<span class="scr-no-restrictions">—</span>';
            }
        }
    }
}

<?php

namespace SimpleCouponRestrictions\Admin;

/**
 * Admin View Class
 */
class AdminView {
    
    /**
     * Render the admin page
     */
    public function render() {
        $restricted_coupons = get_option( 'scr_restricted_coupons', array() );
        $restricted_customers = $this->get_restricted_customers();
        
        ?>
        <div class="wrap scr-admin-wrap">
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-tickets-alt"></span>
                <?php _e( 'Simple Coupon Restrictions', 'simple-coupon-restrictions' ); ?>
            </h1>
            
            <div class="scr-admin-content">
                <!-- Statistics Section -->
                <div class="scr-stats-section">
                    <div class="scr-card">
                        <h2><?php _e( 'Statistics', 'simple-coupon-restrictions' ); ?></h2>
                        <div class="scr-stats-grid">
                            <div class="scr-stat-item">
                                <div class="scr-stat-number"><?php echo count( $restricted_coupons ); ?></div>
                                <div class="scr-stat-label"><?php _e( 'Restricted Coupons', 'simple-coupon-restrictions' ); ?></div>
                            </div>
                            <div class="scr-stat-item">
                                <div class="scr-stat-number"><?php echo count( $restricted_customers ); ?></div>
                                <div class="scr-stat-label"><?php _e( 'Restricted Customers', 'simple-coupon-restrictions' ); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Settings Section -->
                <div class="scr-settings-section">
                    <div class="scr-card">
                        <h2><?php _e( 'Restricted Coupons', 'simple-coupon-restrictions' ); ?></h2>
                        <p class="description">
                            <?php _e( 'Add coupon codes that will block customers from using any coupons in future orders once used.', 'simple-coupon-restrictions' ); ?>
                        </p>
                        
                        <form method="post" action="" class="scr-coupon-form">
                            <?php wp_nonce_field( 'scr_save_settings', 'scr_nonce' ); ?>
                            
                            <div class="scr-coupon-search">
                                <div class="scr-search-wrapper">
                                    <input type="text" id="scr-coupon-search" placeholder="<?php _e( 'Type to search coupons by code or description...', 'simple-coupon-restrictions' ); ?>" class="regular-text" />
                                    <span class="scr-search-loading" style="display: none;">
                                        <span class="dashicons dashicons-update-alt scr-spin"></span>
                                    </span>
                                    <button type="button" id="scr-clear-search" class="button button-link" style="display: none;">
                                        <span class="dashicons dashicons-no-alt"></span>
                                    </button>
                                </div>
                                <button type="button" id="scr-add-selected-coupon" class="button button-primary" disabled>
                                    <span class="dashicons dashicons-plus-alt"></span>
                                    <?php _e( 'Add Coupon', 'simple-coupon-restrictions' ); ?>
                                </button>
                            </div>
                            <div class="scr-search-help">
                                <p class="description">
                                    <?php _e( 'Start typing to search for existing coupons. You can search by coupon code or description.', 'simple-coupon-restrictions' ); ?>
                                </p>
                            </div>
                            
                            <div class="scr-coupon-list" id="scr-coupon-list">
                                <?php if ( ! empty( $restricted_coupons ) ) : ?>
                                    <?php foreach ( $restricted_coupons as $coupon ) : ?>
                                        <div class="scr-coupon-item">
                                            <input type="hidden" name="scr_restricted_coupons[]" value="<?php echo esc_attr( $coupon ); ?>" />
                                            <span class="scr-coupon-code"><?php echo esc_html( $coupon ); ?></span>
                                            <button type="button" class="button button-small scr-remove-coupon">
                                                <span class="dashicons dashicons-trash"></span>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <div class="scr-empty-state scr-empty-coupons">
                                        <span class="dashicons dashicons-tickets-alt"></span>
                                        <p><?php _e( 'No restricted coupons configured yet.', 'simple-coupon-restrictions' ); ?></p>
                                        <p class="description"><?php _e( 'Use the search above to find and add coupons to the restricted list.', 'simple-coupon-restrictions' ); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="scr-coupon-actions">
                                <button type="submit" name="scr_save_settings" class="button button-primary">
                                    <span class="dashicons dashicons-saved"></span>
                                    <?php _e( 'Save Settings', 'simple-coupon-restrictions' ); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Restricted Customers Section -->
                <div class="scr-customers-section">
                    <div class="scr-card">
                        <h2><?php _e( 'Restricted Customers', 'simple-coupon-restrictions' ); ?></h2>
                        <p class="description">
                            <?php _e( 'Customers who have used restricted coupons and are blocked from using any coupons in future orders.', 'simple-coupon-restrictions' ); ?>
                        </p>
                        
                        <div id="scr-customers-container">
                            <?php $this->render_customers_table( $restricted_customers, 1 ); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get restricted customers with pagination
     */
    private function get_restricted_customers( $page = 1, $per_page = null ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'scr_restricted_customers';
        
        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
            return array();
        }
        
        // Apply filter for per_page with default of 10
        if ( $per_page === null ) {
            $per_page = apply_filters( 'scr_customers_per_page', 10 );
        }
        
        $offset = ( $page - 1 ) * $per_page;
        
        $results = $wpdb->get_results( $wpdb->prepare( "
            SELECT 
                customer_id,
                coupon_code,
                restricted_at
            FROM {$table_name}
            ORDER BY restricted_at DESC
            LIMIT %d OFFSET %d
        ", $per_page * 2, $offset ) ); // Get more results to account for grouping
        
        $customers = array();
        
        foreach ( $results as $result ) {
            $customer_id = $result->customer_id;
            
            if ( ! isset( $customers[$customer_id] ) ) {
                $user = get_user_by( 'id', $customer_id );
                if ( $user ) {
                    $customers[$customer_id] = array(
                        'id' => $customer_id,
                        'name' => $user->display_name,
                        'email' => $user->user_email,
                        'coupons' => array(),
                        'restricted_date' => date_i18n( get_option( 'date_format' ), strtotime( $result->restricted_at ) )
                    );
                }
            }
            
            if ( isset( $customers[$customer_id] ) ) {
                $customers[$customer_id]['coupons'][] = $result->coupon_code;
            }
        }
        
        // Remove duplicates from coupons array
        foreach ( $customers as &$customer ) {
            $customer['coupons'] = array_unique( $customer['coupons'] );
        }
        
        // Limit to actual per_page after grouping
        $customers = array_slice( array_values( $customers ), 0, $per_page );
        
        return $customers;
    }
    
    /**
     * Get total count of restricted customers
     */
    private function get_restricted_customers_count() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'scr_restricted_customers';
        
        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
            return 0;
        }
        
        return $wpdb->get_var( "
            SELECT COUNT(DISTINCT customer_id)
            FROM {$table_name}
        " );
    }
    
    /**
     * Render customers table with pagination
     */
    public function render_customers_table( $customers = null, $page = 1 ) {
        $per_page = apply_filters( 'scr_customers_per_page', 10 );
        
        if ( $customers === null ) {
            $customers = $this->get_restricted_customers( $page, $per_page );
        }
        
        $total_customers = $this->get_restricted_customers_count();
        $total_pages = ceil( $total_customers / $per_page );
        
        ?>
        <?php if ( ! empty( $customers ) ) : ?>
            <div class="scr-customers-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e( 'Customer', 'simple-coupon-restrictions' ); ?></th>
                            <th><?php _e( 'Restricted Coupons', 'simple-coupon-restrictions' ); ?></th>
                            <th><?php _e( 'Restricted Date', 'simple-coupon-restrictions' ); ?></th>
                            <th><?php _e( 'Actions', 'simple-coupon-restrictions' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $customers as $customer ) : ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html( $customer['name'] ); ?></strong><br>
                                    <small><?php echo esc_html( $customer['email'] ); ?></small>
                                </td>
                                <td>
                                    <?php foreach ( $customer['coupons'] as $coupon ) : ?>
                                        <span class="scr-coupon-badge"><?php echo esc_html( $coupon ); ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <?php echo esc_html( $customer['restricted_date'] ); ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url( 'admin.php?page=simple-coupon-restrictions&action=reset_customer&customer_id=' . $customer['id'] . '&_wpnonce=' . wp_create_nonce( 'scr_reset_customer_' . $customer['id'] ) ); ?>" 
                                       class="button button-small button-secondary scr-reset-customer">
                                        <span class="dashicons dashicons-update"></span>
                                        <?php _e( 'Reset', 'simple-coupon-restrictions' ); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ( $total_pages > 1 ) : ?>
                <div class="scr-pagination">
                    <div class="scr-pagination-info">
                        <?php printf( 
                            __( 'Showing page %d of %d (%d total customers)', 'simple-coupon-restrictions' ),
                            $page,
                            $total_pages,
                            $total_customers
                        ); ?>
                    </div>
                    <div class="scr-pagination-controls">
                        <?php if ( $page > 1 ) : ?>
                            <button class="button scr-page-btn" data-page="<?php echo $page - 1; ?>">
                                <span class="dashicons dashicons-arrow-left-alt2"></span>
                                <?php _e( 'Previous', 'simple-coupon-restrictions' ); ?>
                            </button>
                        <?php endif; ?>
                        
                        <?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
                            <?php if ( $i == $page ) : ?>
                                <span class="button button-primary scr-current-page"><?php echo $i; ?></span>
                            <?php elseif ( abs( $i - $page ) <= 2 || $i == 1 || $i == $total_pages ) : ?>
                                <button class="button scr-page-btn" data-page="<?php echo $i; ?>"><?php echo $i; ?></button>
                            <?php elseif ( abs( $i - $page ) == 3 ) : ?>
                                <span class="scr-pagination-dots">...</span>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ( $page < $total_pages ) : ?>
                            <button class="button scr-page-btn" data-page="<?php echo $page + 1; ?>">
                                <?php _e( 'Next', 'simple-coupon-restrictions' ); ?>
                                <span class="dashicons dashicons-arrow-right-alt2"></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <div class="scr-empty-state">
                <span class="dashicons dashicons-groups"></span>
                <p><?php _e( 'No customers are currently restricted.', 'simple-coupon-restrictions' ); ?></p>
            </div>
        <?php endif; ?>
        <?php
    }
} 
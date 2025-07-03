jQuery(document).ready(function($) {
    'use strict';
    
    var selectedCoupon = null;
    var searchTimeout = null;
    
    // Initialize coupon search autocomplete
    $('#scr-coupon-search').autocomplete({
        source: function(request, response) {
            // Clear previous timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            // Show loading indicator only once
            if (!$('.scr-search-loading').is(':visible')) {
                $('.scr-search-loading').show();
            }
            $('#scr-add-selected-coupon').prop('disabled', true);
            
            // Debounce the search
            searchTimeout = setTimeout(function() {
                $.post(scr_ajax.ajax_url, {
                    action: 'scr_search_coupons',
                    term: request.term,
                    nonce: scr_ajax.nonce
                }, function(data) {
                    $('.scr-search-loading').hide();
                    
                    if (data.success && data.data.length > 0) {
                        response($.map(data.data, function(item) {
                            return {
                                label: item.label,
                                value: item.code,
                                coupon: item
                            };
                        }));
                    } else {
                        // Show no results message
                        response([{
                            label: 'No coupons found matching "' + request.term + '"',
                            value: '',
                            coupon: null
                        }]);
                    }
                }).fail(function() {
                    $('.scr-search-loading').hide();
                    response([{
                        label: 'Search failed. Please try again.',
                        value: '',
                        coupon: null
                    }]);
                });
            }, 300); // 300ms debounce
        },
        minLength: 2,
        select: function(event, ui) {
            // Prevent selection of no-results items
            if (!ui.item.coupon) {
                return false;
            }
            
            selectedCoupon = ui.item.coupon;
            $(this).val(ui.item.coupon.code);
            $('#scr-add-selected-coupon').prop('disabled', false);
            $('#scr-clear-search').show();
            return false;
        },
        focus: function(event, ui) {
            // Prevent focus on no-results items
            if (!ui.item.coupon) {
                return false;
            }
            $(this).val(ui.item.coupon.code);
            return false;
        },
        search: function() {
            selectedCoupon = null;
            $('#scr-add-selected-coupon').prop('disabled', true);
        },
        close: function() {
            $('.scr-search-loading').hide();
        }
    }).autocomplete('instance')._renderItem = function(ul, item) {
        // Custom rendering for no-results items
        if (!item.coupon) {
            return $('<li>')
                .addClass('ui-menu-item-no-results')
                .append('<div class="ui-menu-item-wrapper" style="font-style: italic; color: #666;">' + item.label + '</div>')
                .appendTo(ul);
        }
        
        // Default rendering for regular items
        return $('<li>')
            .append('<div class="ui-menu-item-wrapper">' + item.label + '</div>')
            .appendTo(ul);
    };
    
    // Handle input changes
    $('#scr-coupon-search').on('input', function() {
        var value = $(this).val().trim();
        if (value.length === 0) {
            clearSearch();
        } else {
            $('#scr-clear-search').show();
        }
    });
    
    // Clear search functionality
    $('#scr-clear-search').on('click', function() {
        clearSearch();
    });
    
    function clearSearch() {
        $('#scr-coupon-search').val('');
        $('#scr-clear-search').hide();
        $('.scr-search-loading').hide();
        $('#scr-add-selected-coupon').prop('disabled', true);
        selectedCoupon = null;
        $('#scr-coupon-search').autocomplete('close');
    }
    
    // Add selected coupon to list
    $('#scr-add-selected-coupon').on('click', function() {
        var searchInput = $('#scr-coupon-search');
        var couponCode = searchInput.val().trim();
        
        if (!couponCode) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Coupon Code',
                text: 'Please enter or select a coupon code.',
                confirmButtonColor: '#0073aa'
            });
            return;
        }
        
        // Check if coupon already exists
        var exists = false;
        $('.scr-coupon-item input[type="hidden"]').each(function() {
            if ($(this).val() === couponCode) {
                exists = true;
                return false;
            }
        });
        
        if (exists) {
            Swal.fire({
                icon: 'info',
                title: 'Coupon Already Added',
                text: 'This coupon is already in the restricted list.',
                confirmButtonColor: '#0073aa'
            });
            return;
        }
        
        // Add coupon to list
        addCouponToList(couponCode);
        
        // Clear search
        clearSearch();
        
        // Show success message
        Swal.fire({
            title: 'Coupon Added!',
            text: 'The coupon "' + couponCode + '" has been added to the restricted list.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    });
    
    // Handle Enter key in search input
    $('#scr-coupon-search').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#scr-add-selected-coupon').click();
        }
    });
    
    // Remove coupon from list
    $(document).on('click', '.scr-remove-coupon', function() {
        var $couponItem = $(this).closest('.scr-coupon-item');
        var couponCode = $couponItem.find('.scr-coupon-code').text();
        
        Swal.fire({
            title: 'Remove Coupon?',
            text: 'Are you sure you want to remove "' + couponCode + '" from the restricted list?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, remove it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $couponItem.fadeOut(300, function() {
                    $(this).remove();
                    
                    // Check if list is empty and show empty state
                    var couponList = $('#scr-coupon-list');
                    if (couponList.find('.scr-coupon-item').length === 0) {
                        var emptyState = $('<div class="scr-empty-state scr-empty-coupons">' +
                            '<span class="dashicons dashicons-tickets-alt"></span>' +
                            '<p>No restricted coupons configured yet.</p>' +
                            '<p class="description">Use the search above to find and add coupons to the restricted list.</p>' +
                        '</div>');
                        couponList.append(emptyState);
                    }
                });
                
                Swal.fire({
                    title: 'Removed!',
                    text: 'The coupon has been removed from the restricted list.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    });
    
    // Confirm customer reset (delegated event handler)
    $(document).on('click', '.scr-reset-customer', function(e) {
        e.preventDefault();
        var $button = $(this);
        var customerName = $button.closest('tr').find('td:first').text();
        
        Swal.fire({
            title: 'Reset Customer Restrictions?',
            text: 'Are you sure you want to remove all coupon restrictions for ' + customerName + '?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, reset restrictions!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit the form or redirect to the reset URL
                window.location.href = $button.attr('href');
            }
        });
    });
    
    // AJAX Pagination for customers
    $(document).on('click', '.scr-page-btn', function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        loadCustomersPage(page);
    });
    
    function loadCustomersPage(page) {
        var $container = $('#scr-customers-container');
        
        // Show loading state
        $container.html('<div class="scr-customers-loading"><span class="dashicons dashicons-update-alt scr-spin"></span><p>Loading customers...</p></div>');
        
        $.post(scr_ajax.ajax_url, {
            action: 'scr_load_customers_page',
            page: page,
            nonce: scr_ajax.nonce
        }, function(response) {
            if (response.success) {
                $container.html(response.data.html);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Loading Error',
                    text: 'Failed to load customers page. Please try again.',
                    confirmButtonColor: '#0073aa'
                });
                // Reload page 1 as fallback
                loadCustomersPage(1);
            }
        }).fail(function() {
            Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'Failed to load customers page. Please check your connection and try again.',
                confirmButtonColor: '#0073aa'
            });
            // Reload page 1 as fallback
            loadCustomersPage(1);
        });
    }
    
    /**
     * Add coupon to the restricted list
     */
    function addCouponToList(couponCode) {
        var couponList = $('#scr-coupon-list');
        
        // Remove empty state if it exists
        couponList.find('.scr-empty-coupons').remove();
        
        var couponItem = $('<div class="scr-coupon-item">' +
            '<input type="hidden" name="scr_restricted_coupons[]" value="' + escapeHtml(couponCode) + '" />' +
            '<span class="scr-coupon-code">' + escapeHtml(couponCode) + '</span>' +
            '<button type="button" class="button button-small scr-remove-coupon">' +
                '<span class="dashicons dashicons-trash"></span>' +
            '</button>' +
        '</div>');
        
        couponList.append(couponItem);
        
        // Add animation
        couponItem.hide().fadeIn(300);
    }
    
    /**
     * Escape HTML entities
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) {
            return map[m];
        });
    }
    
    // Auto-save draft functionality (optional)
    var saveTimeout;
    $('#scr-coupon-search').on('input', function() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(function() {
            // Could implement auto-save draft here
        }, 2000);
    });
    
    // Enhanced table interactions
    $('.scr-customers-table tr').hover(
        function() {
            $(this).addClass('hover');
        },
        function() {
            $(this).removeClass('hover');
        }
    );
    
    // Smooth scrolling for internal links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        var target = $(this.getAttribute('href'));
        if (target.length) {
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 100
            }, 600);
        }
    });
    
    // Initialize tooltips if needed
    if (typeof $.fn.tooltip !== 'undefined') {
        $('[data-tooltip]').tooltip();
    }
}); 
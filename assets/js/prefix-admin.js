/**
 * Simple Coupon Restrictions - Prefix Admin JavaScript
 * JavaScript functionality for the prefix exclusion admin interface
 */

(function($) {
    'use strict';

    /**
     * Prefix Admin Handler
     */
    const PrefixAdmin = {
        
        /**
         * Initialize the admin interface
         */
        init: function() {
            this.bindEvents();
            this.validateOnLoad();
        },
        
        /**
         * Get localized string
         */
        getString: function(key, fallback) {
            return (typeof scrPrefixAdmin !== 'undefined' && 
                    scrPrefixAdmin.strings && 
                    scrPrefixAdmin.strings[key]) ? 
                   scrPrefixAdmin.strings[key] : fallback;
        },
        
        /**
         * Get setting value
         */
        getSetting: function(key, fallback) {
            return (typeof scrPrefixAdmin !== 'undefined' && 
                    scrPrefixAdmin.settings && 
                    scrPrefixAdmin.settings[key] !== undefined) ? 
                   scrPrefixAdmin.settings[key] : fallback;
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            $(document).on('input blur', '#scr_excluded_coupon_prefixes', this.handleInputChange.bind(this));
            $(document).on('keydown', '#scr_excluded_coupon_prefixes', this.handleKeyDown.bind(this));
            $(document).on('paste', '#scr_excluded_coupon_prefixes', this.handlePaste.bind(this));
            
            // Form submission validation
            $('form#post').on('submit', this.handleFormSubmit.bind(this));
            
            // Help tip enhancement
            this.enhanceHelpTip();
        },
        
        /**
         * Handle input changes with real-time validation
         */
        handleInputChange: function(e) {
            const $input = $(e.target);
            const value = $input.val().trim();
            
            // Clear previous validation
            this.clearValidation($input);
            
            if (value === '') {
                return;
            }
            
            // Validate prefixes
            const validation = this.validatePrefixes(value);
            this.showValidation($input, validation);
            
            // Auto-format on blur
            if (e.type === 'blur' && validation.isValid) {
                $input.val(validation.formattedValue);
            }
        },
        
        /**
         * Handle key down events
         */
        handleKeyDown: function(e) {
            const $input = $(e.target);
            
            // Allow: backspace, delete, tab, escape, enter
            if ($.inArray(e.keyCode, [46, 8, 9, 27, 13]) !== -1 ||
                // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X, Ctrl+Z
                (e.keyCode === 65 && e.ctrlKey === true) ||
                (e.keyCode === 67 && e.ctrlKey === true) ||
                (e.keyCode === 86 && e.ctrlKey === true) ||
                (e.keyCode === 88 && e.ctrlKey === true) ||
                (e.keyCode === 90 && e.ctrlKey === true) ||
                // Allow: home, end, left, right, down, up
                (e.keyCode >= 35 && e.keyCode <= 40)) {
                return;
            }
            
            // Prevent invalid characters
            if (this.isInvalidCharacter(e.keyCode, e.shiftKey)) {
                e.preventDefault();
            }
        },
        
        /**
         * Handle paste events
         */
        handlePaste: function(e) {
            const $input = $(e.target);
            
            setTimeout(() => {
                const value = $input.val();
                const cleaned = this.cleanPrefixInput(value);
                
                if (value !== cleaned) {
                    $input.val(cleaned);
                    this.handleInputChange({target: $input[0], type: 'input'});
                }
            }, 10);
        },
        
        /**
         * Handle form submission
         */
        handleFormSubmit: function(e) {
            const $input = $('#scr_excluded_coupon_prefixes');
            const value = $input.val().trim();
            
            if (value === '') {
                return true;
            }
            
            const validation = this.validatePrefixes(value);
            
            if (!validation.isValid) {
                e.preventDefault();
                this.showValidation($input, validation);
                $input.focus();
                
                // Show admin notice
                this.showAdminNotice(this.getString('validationFailed', 'Please fix the prefix restrictions before saving.'), 'error');
                
                return false;
            }
            
            // Format the value before submission
            $input.val(validation.formattedValue);
            return true;
        },
        
        /**
         * Validate prefixes input
         */
        validatePrefixes: function(value) {
            const result = {
                isValid: true,
                message: '',
                formattedValue: value,
                type: 'success'
            };
            
            if (!value || value.trim() === '') {
                return result;
            }
            
            // Split and clean prefixes
            const prefixes = value.split(',').map(p => p.trim()).filter(p => p.length > 0);
            
            if (prefixes.length === 0) {
                result.isValid = false;
                result.message = this.getString('emptyPrefixes', 'Please enter at least one valid prefix.');
                result.type = 'error';
                return result;
            }
            
            // Validate each prefix
            const invalidPrefixes = [];
            const validPrefixes = [];
            
            prefixes.forEach(prefix => {
                if (this.isValidPrefix(prefix)) {
                    validPrefixes.push(prefix.toLowerCase());
                } else {
                    invalidPrefixes.push(prefix);
                }
            });
            
            if (invalidPrefixes.length > 0) {
                result.isValid = false;
                result.message = this.getString('invalidPrefix', 'Invalid prefix format. Use 2-20 characters: letters, numbers, hyphens only.');
                result.type = 'error';
                return result;
            }
            
            // Check for duplicates
            const uniquePrefixes = [...new Set(validPrefixes)];
            if (uniquePrefixes.length !== validPrefixes.length) {
                result.message = this.getString('duplicateRemoved', 'Duplicate prefixes have been removed.');
                result.type = 'info';
            }
            
            result.formattedValue = uniquePrefixes.join(', ');
            result.message = result.message || this.getString('prefixesValid', `${uniquePrefixes.length} prefix(es) will be excluded.`);
            
            return result;
        },
        
        /**
         * Check if a prefix is valid
         */
        isValidPrefix: function(prefix) {
            const minLength = this.getSetting('minLength', 2);
            const maxLength = this.getSetting('maxLength', 20);
            const allowedChars = this.getSetting('allowedChars', 'a-zA-Z0-9-');
            
            const regex = new RegExp(`^[${allowedChars}]{${minLength},${maxLength}}$`);
            return regex.test(prefix);
        },
        
        /**
         * Check if character is invalid for prefix input
         */
        isInvalidCharacter: function(keyCode, shiftKey) {
            // Allow alphanumeric, comma, space, hyphen
            const char = String.fromCharCode(keyCode);
            return !/[a-zA-Z0-9,\s-]/.test(char) && !shiftKey;
        },
        
        /**
         * Clean prefix input by removing invalid characters
         */
        cleanPrefixInput: function(value) {
            return value.replace(/[^a-zA-Z0-9,\s-]/g, '');
        },
        
        /**
         * Show validation message
         */
        showValidation: function($input, validation) {
            const $field = $input.closest('.scr_excluded_coupon_prefixes_field');
            
            // Remove existing validation
            this.clearValidation($input);
            
            // Add validation class
            $field.addClass(`has-${validation.type}`);
            
            // Add validation message
            if (validation.message) {
                const $message = $('<span class="scr-validation-message ' + validation.type + '">' + validation.message + '</span>');
                $input.after($message);
            }
        },
        
        /**
         * Clear validation state
         */
        clearValidation: function($input) {
            const $field = $input.closest('.scr_excluded_coupon_prefixes_field');
            $field.removeClass('has-error has-success has-info');
            $field.find('.scr-validation-message').remove();
        },
        
        /**
         * Validate on page load
         */
        validateOnLoad: function() {
            const $input = $('#scr_excluded_coupon_prefixes');
            const value = $input.val().trim();
            
            if (value) {
                const validation = this.validatePrefixes(value);
                if (validation.type !== 'error') {
                    this.showValidation($input, validation);
                }
            }
        },
        
        /**
         * Enhance help tip with additional functionality
         */
        enhanceHelpTip: function() {
            const $helpTip = $('.scr_excluded_coupon_prefixes_field .woocommerce-help-tip');
            
            if ($helpTip.length) {
                // Add click handler for mobile devices
                $helpTip.on('click touchstart', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const $this = $(this);
                    const tipText = $this.attr('data-tip');
                    
                    if (tipText) {
                        // Show tooltip for mobile
                        const $tooltip = $('<div class="scr-mobile-tooltip">' + tipText + '</div>');
                        $this.after($tooltip);
                        
                        setTimeout(() => {
                            $tooltip.fadeOut(300, function() {
                                $(this).remove();
                            });
                        }, 3000);
                    }
                });
            }
        },
        
        /**
         * Show admin notice
         */
        showAdminNotice: function(message, type = 'info') {
            const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            
            // Insert after h1 or at top of content
            const $target = $('.wrap h1').first();
            if ($target.length) {
                $target.after($notice);
            } else {
                $('.wrap').prepend($notice);
            }
            
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Make dismissible
            $notice.find('.notice-dismiss').on('click', function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        }
    };

    /**
     * Initialize when DOM is ready
     */
    $(document).ready(function() {
        // Only initialize on coupon edit pages
        if ($('#scr_excluded_coupon_prefixes').length) {
            PrefixAdmin.init();
        }
    });

    // Expose to global scope for debugging
    window.SCRPrefixAdmin = PrefixAdmin;

})(jQuery);

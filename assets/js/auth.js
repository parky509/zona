/**
 * ZonaTech NG - Authentication JavaScript
 */

(function($) {
    'use strict';
    
    // Helper function for notifications with fallback
    function showNotification(message, type) {
        if (typeof ZonaTechNotify !== 'undefined' && typeof ZonaTechNotify.show === 'function') {
            ZonaTechNotify.show(message, type);
        } else {
            alert(message);
        }
    }
    
    // Define ZonaTechAuth object first, then initialize
    const ZonaTechAuth = {
        init: function() {
            this.initLoginForm();
            this.initRegisterForm();
            this.initResetPasswordForm();
            this.initProfileForm();
            this.initChangePasswordForm();
            this.initLogout();
        },
        
        // Login Form
        initLoginForm: function() {
            $('#zonatech-login-form').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.html();
                
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Logging in...');
                
                const data = {
                    action: 'zonatech_login',
                    nonce: zonatech_ajax.nonce,
                    email: form.find('[name="email"]').val(),
                    password: form.find('[name="password"]').val(),
                    remember: form.find('[name="remember"]').is(':checked') ? 'true' : 'false'
                };
                
                $.ajax({
                    url: zonatech_ajax.ajax_url,
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            showNotification(response.data.message, 'success');
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 1000);
                        } else {
                            showNotification(response.data.message || 'Login failed. Please try again.', 'error');
                            submitBtn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Login error:', status, error);
                        showNotification('An error occurred. Please try again.', 'error');
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
        },
        
        // Register Form
        initRegisterForm: function() {
            $('#zonatech-register-form').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.html();
                
                // Basic validation
                const password = form.find('[name="password"]').val();
                const confirmPassword = form.find('[name="confirm_password"]').val();
                
                if (password !== confirmPassword) {
                    showNotification('Passwords do not match.', 'error');
                    return;
                }
                
                if (password.length < 6) {
                    showNotification('Password must be at least 6 characters.', 'error');
                    return;
                }
                
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating account...');
                
                const data = {
                    action: 'zonatech_register',
                    nonce: zonatech_ajax.nonce,
                    first_name: form.find('[name="first_name"]').val(),
                    last_name: form.find('[name="last_name"]').val(),
                    email: form.find('[name="email"]').val(),
                    phone: form.find('[name="phone"]').val(),
                    password: password,
                    confirm_password: confirmPassword
                };
                
                $.ajax({
                    url: zonatech_ajax.ajax_url,
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            showNotification(response.data.message, 'success');
                            setTimeout(() => {
                                window.location.href = response.data.redirect;
                            }, 1000);
                        } else {
                            showNotification(response.data.message, 'error');
                            submitBtn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function() {
                        showNotification('An error occurred. Please try again.', 'error');
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
        },
        
        // Reset Password Form
        initResetPasswordForm: function() {
            $('#zonatech-reset-form').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.html();
                
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');
                
                $.ajax({
                    url: zonatech_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'zonatech_reset_password',
                        nonce: zonatech_ajax.nonce,
                        email: form.find('[name="email"]').val()
                    },
                    success: function(response) {
                        showNotification(response.data.message, response.success ? 'success' : 'info');
                        submitBtn.prop('disabled', false).html(originalText);
                        if (response.success) {
                            form[0].reset();
                        }
                    },
                    error: function() {
                        showNotification('An error occurred. Please try again.', 'error');
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
        },
        
        // Profile Update Form
        initProfileForm: function() {
            $('#zonatech-profile-form').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.html();
                
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
                
                $.ajax({
                    url: zonatech_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'zonatech_update_profile',
                        nonce: zonatech_ajax.nonce,
                        first_name: form.find('[name="first_name"]').val(),
                        last_name: form.find('[name="last_name"]').val(),
                        phone: form.find('[name="phone"]').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification(response.data.message, 'success');
                        } else {
                            showNotification(response.data.message, 'error');
                        }
                        submitBtn.prop('disabled', false).html(originalText);
                    },
                    error: function() {
                        showNotification('An error occurred. Please try again.', 'error');
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
        },
        
        // Change Password Form
        initChangePasswordForm: function() {
            $('#zonatech-change-password-form').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.html();
                
                const newPassword = form.find('[name="new_password"]').val();
                const confirmPassword = form.find('[name="confirm_password"]').val();
                
                if (newPassword !== confirmPassword) {
                    showNotification('New passwords do not match.', 'error');
                    return;
                }
                
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Changing...');
                
                $.ajax({
                    url: zonatech_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'zonatech_change_password',
                        nonce: zonatech_ajax.nonce,
                        current_password: form.find('[name="current_password"]').val(),
                        new_password: newPassword,
                        confirm_password: confirmPassword
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification(response.data.message, 'success');
                            form[0].reset();
                        } else {
                            showNotification(response.data.message, 'error');
                        }
                        submitBtn.prop('disabled', false).html(originalText);
                    },
                    error: function() {
                        showNotification('An error occurred. Please try again.', 'error');
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
        },
        
        // Logout
        initLogout: function() {
            $(document).on('click', '.zonatech-logout', function(e) {
                e.preventDefault();
                
                if (!confirm('Are you sure you want to logout?')) return;
                
                $.ajax({
                    url: zonatech_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'zonatech_logout',
                        nonce: zonatech_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.href = response.data.redirect;
                        }
                    }
                });
            });
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        ZonaTechAuth.init();
    });
    
    window.ZonaTechAuth = ZonaTechAuth;
    
})(jQuery);
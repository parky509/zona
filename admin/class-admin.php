<?php
/**
 * Admin Panel Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZonaTech_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_notices', array($this, 'show_setup_notice'));
        
        // AJAX handlers for admin actions
        add_action('wp_ajax_zonatech_save_pricing', array($this, 'handle_save_pricing'));
        add_action('wp_ajax_zonatech_get_pricing', array($this, 'handle_get_pricing'));
    }
    
    public function show_setup_notice() {
        // Only show on ZonaTech pages or when keys are not configured
        $screen = get_current_screen();
        
        if (empty(ZONATECH_PAYSTACK_PUBLIC_KEY) || empty(ZONATECH_PAYSTACK_SECRET_KEY)) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>ZonaTech NG:</strong> Paystack API keys are not configured. 
                    Payment functionality will not work until you configure your keys.
                    <a href="<?php echo admin_url('admin.php?page=zonatech-settings'); ?>">Configure now</a>
                </p>
            </div>
            <?php
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'ZonaTech NG',
            'ZonaTech NG',
            'manage_options',
            'zonatech-ng',
            array($this, 'render_dashboard'),
            'dashicons-welcome-learn-more',
            30
        );
        
        add_submenu_page(
            'zonatech-ng',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'zonatech-ng',
            array($this, 'render_dashboard')
        );
        
        add_submenu_page(
            'zonatech-ng',
            'Questions',
            'Questions',
            'manage_options',
            'zonatech-questions',
            array($this, 'render_questions')
        );
        
        add_submenu_page(
            'zonatech-ng',
            'Scratch Cards',
            'Scratch Cards',
            'manage_options',
            'zonatech-cards',
            array($this, 'render_cards')
        );
        
        add_submenu_page(
            'zonatech-ng',
            'Activity Log',
            'Activity Log',
            'manage_options',
            'zonatech-activity',
            array($this, 'render_activity')
        );
        
        add_submenu_page(
            'zonatech-ng',
            'Users',
            'Users',
            'manage_options',
            'zonatech-users',
            array($this, 'render_users')
        );
        
        add_submenu_page(
            'zonatech-ng',
            'Feedback',
            'Feedback',
            'manage_options',
            'zonatech-feedback',
            array($this, 'render_feedback')
        );
        
        add_submenu_page(
            'zonatech-ng',
            'Pricing',
            'Pricing',
            'manage_options',
            'zonatech-pricing',
            array($this, 'render_pricing')
        );
        
        add_submenu_page(
            'zonatech-ng',
            'Settings',
            'Settings',
            'manage_options',
            'zonatech-settings',
            array($this, 'render_settings')
        );
    }
    
    public function register_settings() {
        // Paystack settings
        register_setting('zonatech_settings', 'zonatech_paystack_public_key');
        register_setting('zonatech_settings', 'zonatech_paystack_secret_key');
        
        // Support settings
        register_setting('zonatech_settings', 'zonatech_whatsapp_number');
        register_setting('zonatech_settings', 'zonatech_support_email');
        
        // Pricing settings - Exam types
        register_setting('zonatech_pricing', 'zonatech_subject_price');
        register_setting('zonatech_pricing', 'zonatech_monthly_price');
        register_setting('zonatech_pricing', 'zonatech_6month_price');
        register_setting('zonatech_pricing', 'zonatech_free_questions_limit');
        
        // Pricing settings - Scratch Cards
        register_setting('zonatech_pricing', 'zonatech_scratch_card_price');
        register_setting('zonatech_pricing', 'zonatech_waec_card_price');
        register_setting('zonatech_pricing', 'zonatech_neco_card_price');
        register_setting('zonatech_pricing', 'zonatech_jamb_card_price');
        
        // Pricing settings - NIN Services
        register_setting('zonatech_pricing', 'zonatech_nin_slip_price');
        register_setting('zonatech_pricing', 'zonatech_nin_standard_slip_price');
        register_setting('zonatech_pricing', 'zonatech_nin_slip_download_price');
        register_setting('zonatech_pricing', 'zonatech_nin_modification_price');
        register_setting('zonatech_pricing', 'zonatech_nin_dob_correction_price');
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'zonatech') === false) {
            return;
        }
        
        wp_enqueue_style('zonatech-admin', ZONATECH_PLUGIN_URL . 'assets/css/admin.css', array(), ZONATECH_VERSION);
        wp_enqueue_script('zonatech-admin', ZONATECH_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), ZONATECH_VERSION, true);
        
        wp_localize_script('zonatech-admin', 'zonatech_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('zonatech_nonce')
        ));
    }
    
    /**
     * Get price from options with fallback to constant
     */
    public static function get_price($option_name, $constant_name) {
        $price = get_option($option_name);
        if ($price === false || $price === '') {
            return defined($constant_name) ? constant($constant_name) : 0;
        }
        return intval($price);
    }
    
    /**
     * Handle AJAX save pricing
     */
    public function handle_save_pricing() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
            return;
        }
        
        $pricing_fields = array(
            'zonatech_subject_price',
            'zonatech_monthly_price',
            'zonatech_6month_price',
            'zonatech_free_questions_limit',
            'zonatech_scratch_card_price',
            'zonatech_waec_card_price',
            'zonatech_neco_card_price',
            'zonatech_jamb_card_price',
            'zonatech_nin_slip_price',
            'zonatech_nin_standard_slip_price',
            'zonatech_nin_slip_download_price',
            'zonatech_nin_modification_price',
            'zonatech_nin_dob_correction_price'
        );
        
        foreach ($pricing_fields as $field) {
            if (isset($_POST[$field])) {
                $value = intval($_POST[$field]);
                update_option($field, $value);
            }
        }
        
        wp_send_json_success(array('message' => 'Pricing updated successfully!'));
    }
    
    /**
     * Handle AJAX get pricing
     */
    public function handle_get_pricing() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
            return;
        }
        
        $pricing = array(
            'subject_price' => self::get_price('zonatech_subject_price', 'ZONATECH_SUBJECT_PRICE'),
            'monthly_price' => self::get_price('zonatech_monthly_price', 'ZONATECH_MONTHLY_PRICE'),
            '6month_price' => self::get_price('zonatech_6month_price', 'ZONATECH_6MONTH_PRICE'),
            'free_questions_limit' => self::get_price('zonatech_free_questions_limit', 'ZONATECH_FREE_QUESTIONS_LIMIT'),
            'scratch_card_price' => self::get_price('zonatech_scratch_card_price', 'ZONATECH_SCRATCH_CARD_PRICE'),
            'waec_card_price' => self::get_price('zonatech_waec_card_price', 'ZONATECH_WAEC_CARD_PRICE'),
            'neco_card_price' => self::get_price('zonatech_neco_card_price', 'ZONATECH_NECO_CARD_PRICE'),
            'jamb_card_price' => self::get_price('zonatech_jamb_card_price', 'ZONATECH_SCRATCH_CARD_PRICE'),
            'nin_slip_price' => self::get_price('zonatech_nin_slip_price', 'ZONATECH_NIN_SLIP_PRICE'),
            'nin_standard_slip_price' => self::get_price('zonatech_nin_standard_slip_price', 'ZONATECH_NIN_STANDARD_SLIP_PRICE'),
            'nin_slip_download_price' => self::get_price('zonatech_nin_slip_download_price', 'ZONATECH_NIN_SLIP_DOWNLOAD_PRICE'),
            'nin_modification_price' => self::get_price('zonatech_nin_modification_price', 'ZONATECH_NIN_MODIFICATION_PRICE'),
            'nin_dob_correction_price' => self::get_price('zonatech_nin_dob_correction_price', 'ZONATECH_NIN_DOB_CORRECTION_PRICE'),
        );
        
        wp_send_json_success($pricing);
    }
    
    public function render_dashboard() {
        global $wpdb;
        
        // Get statistics
        $users_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
        
        $table_purchases = $wpdb->prefix . 'zonatech_purchases';
        $total_revenue = $wpdb->get_var("SELECT SUM(amount) FROM $table_purchases WHERE status = 'completed'") ?? 0;
        $purchases_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_purchases WHERE status = 'completed'");
        
        $table_quiz = $wpdb->prefix . 'zonatech_quiz_results';
        $quizzes_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_quiz");
        
        $recent_activities = ZonaTech_Activity_Log::get_recent_activities(20);
        
        include ZONATECH_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    public function render_questions() {
        include ZONATECH_PLUGIN_DIR . 'admin/views/questions.php';
    }
    
    public function render_cards() {
        include ZONATECH_PLUGIN_DIR . 'admin/views/cards.php';
    }
    
    public function render_activity() {
        include ZONATECH_PLUGIN_DIR . 'admin/views/activity.php';
    }
    
    public function render_users() {
        include ZONATECH_PLUGIN_DIR . 'admin/views/users.php';
    }
    
    public function render_feedback() {
        include ZONATECH_PLUGIN_DIR . 'admin/views/feedback.php';
    }
    
    public function render_pricing() {
        include ZONATECH_PLUGIN_DIR . 'admin/views/pricing.php';
    }
    
    public function render_settings() {
        include ZONATECH_PLUGIN_DIR . 'admin/views/settings.php';
    }
}
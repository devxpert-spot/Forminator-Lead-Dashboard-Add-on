<?php
/**
 * Plugin Name: Forminator Lead Dashboard by DevXpert
 * Plugin URI: https://www.linkedin.com/in/anupkankale/
 * Description: A powerful Lead Management Dashboard addon for Forminator. Track SEO leads, manage feedback, and categorize leads as positive/negative.
 * Version: 1.0.0
 * Author: Anup Kankale
 * Author URI: https://www.linkedin.com/in/anupkankale/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: forminator-lead-dashboard
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * 
 * This plugin requires Forminator to be installed and activated.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FLD_VERSION', '1.0.0');
define('FLD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FLD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FLD_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class Forminator_Lead_Dashboard {

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Set up roles/caps early (before init)
        add_action('plugins_loaded', array($this, 'setup_roles'), 5);

        // Check if Forminator is active
        add_action('plugins_loaded', array($this, 'check_forminator'));

        // Initialize plugin
        add_action('plugins_loaded', array($this, 'init'));

        // Activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));

        // Deactivation hook
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Check if Forminator is installed and active
     */
    public function check_forminator() {
        if (!class_exists('Forminator')) {
            add_action('admin_notices', array($this, 'forminator_missing_notice'));
            return false;
        }
        return true;
    }

    /**
     * Admin notice if Forminator is not installed
     */
    public function forminator_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('Forminator Lead Dashboard requires Forminator plugin to be installed and activated.', 'forminator-lead-dashboard'); ?></p>
        </div>
        <?php
    }

    /**
     * Initialize plugin
     */
    public function init() {
        if (!$this->check_forminator()) {
            return;
        }

        // Load includes
        $this->includes();

        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

            // Sales Admin restrictions: lock down the WP admin to our pages only
            add_action('admin_init', array($this, 'restrict_sales_admin_access'));
            add_action('admin_menu', array($this, 'restrict_sales_admin_menu'), 999);
        }

        // Redirect Sales Admin users to Lead Dashboard after login
        // login_redirect covers wp-login.php
        add_filter('login_redirect', array($this, 'sales_admin_login_redirect'), 999, 3);
        // wp_login covers generic custom login forms
        add_action('wp_login', array($this, 'sales_admin_wp_login_redirect'), 999, 2);
        // woocommerce_login_redirect covers WooCommerce My Account login
        add_filter('woocommerce_login_redirect', array($this, 'sales_admin_woo_login_redirect'), 999, 2);

        // Clean up WP admin bar for Sales Admins
        add_action('admin_bar_menu', array($this, 'restrict_sales_admin_toolbar'), 999);

        // AJAX handlers
        add_action('wp_ajax_fld_get_leads', array($this, 'ajax_get_leads'));
        add_action('wp_ajax_fld_update_lead_status', array($this, 'ajax_update_lead_status'));
        add_action('wp_ajax_fld_add_feedback', array($this, 'ajax_add_feedback'));
        add_action('wp_ajax_fld_get_feedback', array($this, 'ajax_get_feedback'));
        add_action('wp_ajax_fld_delete_feedback', array($this, 'ajax_delete_feedback'));
        add_action('wp_ajax_fld_get_dashboard_stats', array($this, 'ajax_get_dashboard_stats'));
        add_action('wp_ajax_fld_export_leads', array($this, 'ajax_export_leads'));
        add_action('wp_ajax_fld_get_lead', array($this, 'ajax_get_lead'));

        // Role management AJAX — admin only
        add_action('wp_ajax_fld_get_assignable_users', array($this, 'ajax_get_assignable_users'));
        add_action('wp_ajax_fld_assign_sales_admin', array($this, 'ajax_assign_sales_admin'));
        add_action('wp_ajax_fld_remove_sales_admin', array($this, 'ajax_remove_sales_admin'));
    }

    /**
     * Set up roles and capabilities
     */
    public function setup_roles() {
        require_once FLD_PLUGIN_DIR . 'includes/class-fld-roles.php';
        FLD_Roles::setup();
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once FLD_PLUGIN_DIR . 'includes/class-fld-roles.php';
        require_once FLD_PLUGIN_DIR . 'includes/class-fld-database.php';
        require_once FLD_PLUGIN_DIR . 'includes/class-fld-leads.php';
        require_once FLD_PLUGIN_DIR . 'includes/class-fld-feedback.php';
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Set up roles and capabilities
        require_once FLD_PLUGIN_DIR . 'includes/class-fld-roles.php';
        FLD_Roles::setup();

        // Create custom tables
        require_once FLD_PLUGIN_DIR . 'includes/class-fld-database.php';
        FLD_Database::create_tables();

        // Set default options
        add_option('fld_version', FLD_VERSION);

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        require_once FLD_PLUGIN_DIR . 'includes/class-fld-roles.php';
        FLD_Roles::teardown();
        flush_rewrite_rules();
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu — visible to administrators and sales admins
        add_menu_page(
            __('Lead Dashboard', 'forminator-lead-dashboard'),
            __('Lead Dashboard', 'forminator-lead-dashboard'),
            FLD_Roles::CAP,
            'lead-dashboard',
            array($this, 'render_dashboard_page'),
            'dashicons-chart-line',
            30
        );

        // Submenu - Dashboard
        add_submenu_page(
            'lead-dashboard',
            __('Dashboard', 'forminator-lead-dashboard'),
            __('Dashboard', 'forminator-lead-dashboard'),
            FLD_Roles::CAP,
            'lead-dashboard',
            array($this, 'render_dashboard_page')
        );

        // Submenu - All Leads
        add_submenu_page(
            'lead-dashboard',
            __('All Leads', 'forminator-lead-dashboard'),
            __('All Leads', 'forminator-lead-dashboard'),
            FLD_Roles::CAP,
            'lead-dashboard-leads',
            array($this, 'render_leads_page')
        );

        // Submenu - Settings — administrators only
        add_submenu_page(
            'lead-dashboard',
            __('Settings', 'forminator-lead-dashboard'),
            __('Settings', 'forminator-lead-dashboard'),
            'manage_options',
            'lead-dashboard-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'lead-dashboard') === false) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'fld-admin-styles',
            FLD_PLUGIN_URL . 'assets/css/admin-styles.css',
            array(),
            FLD_VERSION
        );

        // Chart.js
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '4.4.0',
            true
        );

        // Admin JS
        wp_enqueue_script(
            'fld-admin-scripts',
            FLD_PLUGIN_URL . 'assets/js/admin-scripts.js',
            array('jquery', 'chartjs'),
            FLD_VERSION,
            true
        );

        // Localize script
        wp_localize_script('fld-admin-scripts', 'fld_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fld_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this?', 'forminator-lead-dashboard'),
                'loading' => __('Loading...', 'forminator-lead-dashboard'),
                'error' => __('An error occurred. Please try again.', 'forminator-lead-dashboard'),
                'success' => __('Success!', 'forminator-lead-dashboard'),
            )
        ));
    }

    /**
     * Render Dashboard Page
     */
    public function render_dashboard_page() {
        include FLD_PLUGIN_DIR . 'templates/dashboard.php';
    }

    /**
     * Render Leads Page
     */
    public function render_leads_page() {
        include FLD_PLUGIN_DIR . 'templates/leads.php';
    }

    /**
     * Render Settings Page (administrators only)
     */
    public function render_settings_page() {
        if (!FLD_Roles::is_admin()) {
            wp_die(__('You do not have permission to access this page.', 'forminator-lead-dashboard'));
        }
        include FLD_PLUGIN_DIR . 'templates/settings.php';
    }

    /**
     * Remove unneeded WP admin bar nodes for Sales Admins.
     * Keeps: site name (home link), user account, logout.
     */
    public function restrict_sales_admin_toolbar($wp_admin_bar) {
        if (!FLD_Roles::can_access() || FLD_Roles::is_admin()) {
            return;
        }

        $remove = array(
            'wp-logo', 'about', 'wporg', 'documentation', 'support-forums',
            'feedback', 'site-name', 'view-site', 'updates', 'comments',
            'new-content', 'edit',
        );

        foreach ($remove as $node) {
            $wp_admin_bar->remove_node($node);
        }
    }

    /**
     * Redirect Sales Admin users to the Lead Dashboard immediately after login.
     */
    public function sales_admin_login_redirect($redirect_to, $request, $user) {
        if ($user instanceof WP_User && in_array(FLD_Roles::ROLE_SLUG, (array) $user->roles, true)) {
            return admin_url('admin.php?page=lead-dashboard');
        }
        return $redirect_to;
    }

    /**
     * Redirect Sales Admins after login via any non-wp-login.php form.
     * wp_login fires on every successful authentication.
     */
    public function sales_admin_wp_login_redirect($user_login, $user) {
        if ($user instanceof WP_User && in_array(FLD_Roles::ROLE_SLUG, (array) $user->roles, true)) {
            wp_safe_redirect(admin_url('admin.php?page=lead-dashboard'));
            exit;
        }
    }

    /**
     * Override WooCommerce's own login redirect for Sales Admin users.
     * woocommerce_login_redirect filter is WooCommerce's final redirect decision.
     */
    public function sales_admin_woo_login_redirect($redirect, $user) {
        if ($user instanceof WP_User && in_array(FLD_Roles::ROLE_SLUG, (array) $user->roles, true)) {
            return admin_url('admin.php?page=lead-dashboard');
        }
        return $redirect;
    }

    /**
     * Remove every WP admin menu item for Sales Admins except our own pages.
     * Runs at admin_menu priority 999 (after all menus are registered).
     */
    public function restrict_sales_admin_menu() {
        if (!FLD_Roles::can_access() || FLD_Roles::is_admin()) {
            return;
        }

        global $menu;

        foreach ($menu as $item) {
            $slug = isset($item[2]) ? $item[2] : '';
            if ($slug && $slug !== 'lead-dashboard') {
                remove_menu_page($slug);
            }
        }
    }

    /**
     * Prevent Sales Admins from visiting any admin page outside our plugin.
     * Runs on admin_init.
     */
    public function restrict_sales_admin_access() {
        // Only applies to Sales Admin (not administrators, not guests)
        if (!FLD_Roles::can_access() || FLD_Roles::is_admin()) {
            return;
        }

        // AJAX calls are always permitted
        if (wp_doing_ajax()) {
            return;
        }

        $script       = isset($_SERVER['SCRIPT_NAME']) ? basename(sanitize_text_field($_SERVER['SCRIPT_NAME'])) : '';
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

        // Pages Sales Admin is allowed to access
        $allowed_pages = array('lead-dashboard', 'lead-dashboard-leads');

        // Allow our plugin pages
        if ($script === 'admin.php' && in_array($current_page, $allowed_pages, true)) {
            return;
        }

        // Allow the user's own profile page (password change, etc.)
        if ($script === 'profile.php') {
            return;
        }

        // Everything else → redirect to Lead Dashboard
        wp_safe_redirect(admin_url('admin.php?page=lead-dashboard'));
        exit;
    }

    /**
     * AJAX: Get Leads
     */
    public function ajax_get_leads() {
        check_ajax_referer('fld_nonce', 'nonce');

        if (!FLD_Roles::can_access()) {
            wp_send_json_error('Unauthorized');
        }

        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        $leads = FLD_Leads::get_leads(array(
            'form_id' => $form_id,
            'status' => $status,
            'page' => $page,
            'per_page' => $per_page,
            'search' => $search
        ));

        wp_send_json_success($leads);
    }

    /**
     * AJAX: Get Single Lead by Entry ID
     */
    public function ajax_get_lead() {
        check_ajax_referer('fld_nonce', 'nonce');

        if (!FLD_Roles::can_access()) {
            wp_send_json_error('Unauthorized');
        }

        $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;

        if (!$entry_id) {
            wp_send_json_error('Invalid entry ID');
        }

        $lead = FLD_Leads::get_lead($entry_id);

        if (!$lead) {
            wp_send_json_error('Lead not found');
        }

        wp_send_json_success($lead);
    }

    /**
     * AJAX: Update Lead Status
     */
    public function ajax_update_lead_status() {
        check_ajax_referer('fld_nonce', 'nonce');

        if (!FLD_Roles::can_access()) {
            wp_send_json_error('Unauthorized');
        }

        $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        if (!$entry_id || !in_array($status, array('new', 'positive', 'negative', 'follow_up', 'converted', 'closed'))) {
            wp_send_json_error('Invalid data');
        }

        $result = FLD_Leads::update_lead_status($entry_id, $status);

        if ($result) {
            wp_send_json_success('Status updated');
        } else {
            wp_send_json_error('Failed to update status');
        }
    }

    /**
     * AJAX: Add Feedback
     */
    public function ajax_add_feedback() {
        check_ajax_referer('fld_nonce', 'nonce');

        if (!FLD_Roles::can_access()) {
            wp_send_json_error('Unauthorized');
        }

        $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;
        $feedback = isset($_POST['feedback']) ? sanitize_textarea_field($_POST['feedback']) : '';
        $rating = isset($_POST['rating']) ? sanitize_text_field($_POST['rating']) : 'neutral';

        if (!$entry_id || empty($feedback)) {
            wp_send_json_error('Invalid data');
        }

        $result = FLD_Feedback::add_feedback(array(
            'entry_id' => $entry_id,
            'feedback' => $feedback,
            'rating' => $rating,
            'user_id' => get_current_user_id()
        ));

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Feedback added',
                'feedback_id' => $result
            ));
        } else {
            wp_send_json_error('Failed to add feedback');
        }
    }

    /**
     * AJAX: Get Feedback
     */
    public function ajax_get_feedback() {
        check_ajax_referer('fld_nonce', 'nonce');

        if (!FLD_Roles::can_access()) {
            wp_send_json_error('Unauthorized');
        }

        $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;

        if (!$entry_id) {
            wp_send_json_error('Invalid entry ID');
        }

        $feedback = FLD_Feedback::get_feedback($entry_id);

        wp_send_json_success($feedback);
    }

    /**
     * AJAX: Delete Feedback
     */
    public function ajax_delete_feedback() {
        check_ajax_referer('fld_nonce', 'nonce');

        if (!FLD_Roles::can_access()) {
            wp_send_json_error('Unauthorized');
        }

        $feedback_id = isset($_POST['feedback_id']) ? intval($_POST['feedback_id']) : 0;

        if (!$feedback_id) {
            wp_send_json_error('Invalid feedback ID');
        }

        // Sales admins can only delete their own feedback entries
        if (!FLD_Roles::is_admin()) {
            $owner = FLD_Feedback::get_feedback_owner($feedback_id);
            if ($owner !== get_current_user_id()) {
                wp_send_json_error('You can only delete your own feedback');
            }
        }

        $result = FLD_Feedback::delete_feedback($feedback_id);

        if ($result) {
            wp_send_json_success('Feedback deleted');
        } else {
            wp_send_json_error('Failed to delete feedback');
        }
    }

    /**
     * AJAX: Get Dashboard Stats
     */
    public function ajax_get_dashboard_stats() {
        check_ajax_referer('fld_nonce', 'nonce');

        if (!FLD_Roles::can_access()) {
            wp_send_json_error('Unauthorized');
        }

        $date_range = isset($_POST['date_range']) ? sanitize_text_field($_POST['date_range']) : '30';

        $stats = FLD_Leads::get_dashboard_stats($date_range);

        wp_send_json_success($stats);
    }

    /**
     * AJAX: Export Leads
     */
    public function ajax_export_leads() {
        check_ajax_referer('fld_nonce', 'nonce');

        if (!FLD_Roles::can_access()) {
            wp_send_json_error('Unauthorized');
        }

        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        $csv_data = FLD_Leads::export_leads_csv($form_id, $status);

        wp_send_json_success(array('csv' => $csv_data));
    }

    /**
     * AJAX: Get all users that can be assigned the sales_admin role
     */
    public function ajax_get_assignable_users() {
        check_ajax_referer('fld_nonce', 'nonce');

        if (!FLD_Roles::is_admin()) {
            wp_send_json_error('Unauthorized');
        }

        // All WP users excluding current administrators
        $all_users = get_users(array('orderby' => 'display_name', 'order' => 'ASC'));
        $sales_admin_ids = array_map(function($u) { return $u->ID; }, FLD_Roles::get_sales_admins());

        $list = array();
        foreach ($all_users as $user) {
            if ($user->has_cap('manage_options')) {
                continue; // skip administrators
            }
            $list[] = array(
                'id'           => $user->ID,
                'name'         => $user->display_name,
                'email'        => $user->user_email,
                'is_sales_admin' => in_array($user->ID, $sales_admin_ids, true),
            );
        }

        wp_send_json_success($list);
    }

    /**
     * AJAX: Assign sales_admin role to a user
     */
    public function ajax_assign_sales_admin() {
        check_ajax_referer('fld_nonce', 'nonce');

        if (!FLD_Roles::is_admin()) {
            wp_send_json_error('Unauthorized');
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

        if (!$user_id) {
            wp_send_json_error('Invalid user ID');
        }

        if (FLD_Roles::assign($user_id)) {
            $user = get_userdata($user_id);
            wp_send_json_success(array(
                'message' => sprintf(__('%s is now a Sales Admin.', 'forminator-lead-dashboard'), $user->display_name),
            ));
        } else {
            wp_send_json_error(__('Could not assign role. Administrators cannot be changed.', 'forminator-lead-dashboard'));
        }
    }

    /**
     * AJAX: Remove sales_admin role from a user
     */
    public function ajax_remove_sales_admin() {
        check_ajax_referer('fld_nonce', 'nonce');

        if (!FLD_Roles::is_admin()) {
            wp_send_json_error('Unauthorized');
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

        if (!$user_id) {
            wp_send_json_error('Invalid user ID');
        }

        if (FLD_Roles::remove($user_id)) {
            $user = get_userdata($user_id);
            wp_send_json_success(array(
                'message' => sprintf(__('%s has been removed from Sales Admin.', 'forminator-lead-dashboard'), $user->display_name),
            ));
        } else {
            wp_send_json_error(__('User is not a Sales Admin.', 'forminator-lead-dashboard'));
        }
    }
}

// Initialize plugin
Forminator_Lead_Dashboard::get_instance();

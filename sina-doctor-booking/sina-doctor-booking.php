<?php
/**
 * Plugin Name:       Sina Doctor Booking - نوبت دهی پزشکان سینا
 * Plugin URI:        https://github.com/ssinazare/ui-ux-pro-max-skill
 * Description:       سیستم کامل نوبت‌دهی پزشکان با پنل بیمار، منشی و مدیر. قابلیت اتصال به ووکامرس، المنتور و پرینت حرارتی. شامل رزرو آنلاین، کد رهگیری، اطلاعیه‌ها و محاسبه خودکار هزینه ویزیت و خدمات.
 * Version:           1.0.0
 * Author:            Sina Nazare
 * Author URI:        https://github.com/ssinazare
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sina-doctor-booking
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

if (!defined('ABSPATH')) exit;

define('SINA_BOOKING_VERSION', '1.0.0');
define('SINA_BOOKING_FILE', __FILE__);
define('SINA_BOOKING_PATH', plugin_dir_path(__FILE__));
define('SINA_BOOKING_URL', plugin_dir_url(__FILE__));
define('SINA_BOOKING_BASENAME', plugin_basename(__FILE__));

// Include core files
require_once SINA_BOOKING_PATH . 'includes/class-database.php';
require_once SINA_BOOKING_PATH . 'includes/class-roles.php';
require_once SINA_BOOKING_PATH . 'includes/class-doctor.php';
require_once SINA_BOOKING_PATH . 'includes/class-schedule.php';
require_once SINA_BOOKING_PATH . 'includes/class-appointment.php';
require_once SINA_BOOKING_PATH . 'includes/class-ajax.php';
require_once SINA_BOOKING_PATH . 'includes/class-woocommerce.php';
require_once SINA_BOOKING_PATH . 'includes/class-elementor.php';
require_once SINA_BOOKING_PATH . 'includes/class-shortcodes.php';
require_once SINA_BOOKING_PATH . 'includes/class-admin.php';

final class Sina_Doctor_Booking {

    private static $instance = null;

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    public function load_textdomain() {
        load_plugin_textdomain('sina-doctor-booking', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function init() {
        // Initialize components
        Sina_Booking_Ajax::init();
        Sina_Booking_WooCommerce::init();
        Sina_Booking_Elementor::init();
        Sina_Booking_Shortcodes::init();
        Sina_Booking_Admin::init();

        // Register rewrite for print invoice
        add_rewrite_rule('^sina-print/([A-Z0-9-]+)/?$', 'index.php?sina_print_code=$matches[1]', 'top');
        add_filter('query_vars', function($vars){
            $vars[] = 'sina_print_code';
            return $vars;
        });
        add_action('template_include', [$this, 'print_template']);
    }

    public function print_template($template) {
        $code = get_query_var('sina_print_code');
        if (!empty($code)) {
            return SINA_BOOKING_PATH . 'public/views/print-invoice.php';
        }
        return $template;
    }

    public function enqueue_public_assets() {
        wp_enqueue_style('sina-booking-public', SINA_BOOKING_URL . 'public/css/public.css', [], SINA_BOOKING_VERSION);
        wp_enqueue_style('vazirmatn-font', 'https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css', [], null);
        
        wp_enqueue_script('sina-booking-public', SINA_BOOKING_URL . 'public/js/public.js', ['jquery'], SINA_BOOKING_VERSION, true);
        wp_localize_script('sina-booking-public', 'sinaBooking', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('sina_booking_nonce'),
            'i18n'     => [
                'select_day' => __('لطفا یک روز را انتخاب کنید', 'sina-doctor-booking'),
                'select_time' => __('لطفا یک ساعت را انتخاب کنید', 'sina-doctor-booking'),
                'loading' => __('در حال بارگذاری...', 'sina-doctor-booking'),
                'no_slots' => __('در این روز نوبت خالی وجود ندارد', 'sina-doctor-booking'),
                'booking_success' => __('نوبت شما با موفقیت ثبت شد', 'sina-doctor-booking'),
            ]
        ]);
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'sina-booking') === false) return;
        
        wp_enqueue_style('sina-booking-admin', SINA_BOOKING_URL . 'admin/css/admin.css', [], SINA_BOOKING_VERSION);
        wp_enqueue_script('sina-booking-admin', SINA_BOOKING_URL . 'admin/js/admin.js', ['jquery', 'wp-color-picker'], SINA_BOOKING_VERSION, true);
        wp_localize_script('sina-booking-admin', 'sinaAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sina_booking_admin_nonce'),
        ]);
    }

    public function activate() {
        Sina_Booking_Database::create_tables();
        Sina_Booking_Roles::add_roles();
        Sina_Booking_Roles::add_caps();

        // Create default pages
        $pages = [
            'sina-booking' => [
                'title' => 'نوبت دهی پزشکان',
                'content' => '[sina_booking]',
            ],
            'sina-patient-panel' => [
                'title' => 'پنل بیمار',
                'content' => '[sina_patient_panel]',
            ],
            'sina-secretary-panel' => [
                'title' => 'پنل منشی',
                'content' => '[sina_secretary_panel]',
            ],
        ];

        foreach ($pages as $slug => $data) {
            if (!get_page_by_path($slug)) {
                wp_insert_post([
                    'post_title'   => $data['title'],
                    'post_name'    => $slug,
                    'post_content' => $data['content'],
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                ]);
            }
        }

        // Default settings
        add_option('sina_booking_settings', [
            'booking_fee' => 5000,
            'currency' => 'تومان',
            'enable_woocommerce' => 0,
            'thermal_width' => '80mm',
            'clinic_name' => 'کلینیک سینا',
            'clinic_address' => '',
            'clinic_phone' => '',
        ]);

        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

function sina_booking() {
    return Sina_Doctor_Booking::get_instance();
}
sina_booking();

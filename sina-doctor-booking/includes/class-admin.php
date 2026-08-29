<?php
if (!defined('ABSPATH')) exit;

class Sina_Booking_Admin {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menus']);
    }

    public static function add_menus() {
        add_menu_page(
            'نوبت دهی سینا',
            'نوبت دهی',
            'sina_manage_appointments',
            'sina-booking',
            [__CLASS__, 'dashboard_page'],
            'dashicons-calendar-alt',
            26
        );

        add_submenu_page('sina-booking', 'داشبورد', 'داشبورد', 'sina_manage_appointments', 'sina-booking', [__CLASS__, 'dashboard_page']);
        add_submenu_page('sina-booking', 'پزشکان', 'پزشکان', 'sina_manage_doctors', 'sina-booking-doctors', [__CLASS__, 'doctors_page']);
        add_submenu_page('sina-booking', 'برنامه کاری', 'برنامه کاری', 'sina_manage_schedules', 'sina-booking-schedules', [__CLASS__, 'schedules_page']);
        add_submenu_page('sina-booking', 'نوبت ها', 'نوبت ها', 'sina_manage_appointments', 'sina-booking-appointments', [__CLASS__, 'appointments_page']);
        add_submenu_page('sina-booking', 'خدمات', 'خدمات و تعرفه', 'sina_manage_services', 'sina-booking-services', [__CLASS__, 'services_page']);
        add_submenu_page('sina-booking', 'اطلاعیه ها', 'اطلاعیه ها', 'sina_manage_all', 'sina-booking-announcements', [__CLASS__, 'announcements_page']);
        add_submenu_page('sina-booking', 'تنظیمات', 'تنظیمات', 'sina_manage_settings', 'sina-booking-settings', [__CLASS__, 'settings_page']);
    }

    public static function dashboard_page() {
        include SINA_BOOKING_PATH . 'admin/views/dashboard.php';
    }
    public static function doctors_page() {
        include SINA_BOOKING_PATH . 'admin/views/doctors.php';
    }
    public static function schedules_page() {
        include SINA_BOOKING_PATH . 'admin/views/schedules.php';
    }
    public static function appointments_page() {
        include SINA_BOOKING_PATH . 'admin/views/appointments.php';
    }
    public static function services_page() {
        include SINA_BOOKING_PATH . 'admin/views/services.php';
    }
    public static function announcements_page() {
        include SINA_BOOKING_PATH . 'admin/views/announcements.php';
    }
    public static function settings_page() {
        include SINA_BOOKING_PATH . 'admin/views/settings.php';
    }
}

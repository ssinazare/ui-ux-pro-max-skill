<?php
// If uninstall not called from WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Option to keep data? For now we keep tables unless user wants delete
// Uncomment to delete tables on uninstall:

$tables = [
    $wpdb->prefix . 'sina_doctors',
    $wpdb->prefix . 'sina_services',
    $wpdb->prefix . 'sina_schedules',
    $wpdb->prefix . 'sina_slots',
    $wpdb->prefix . 'sina_appointments',
    $wpdb->prefix . 'sina_announcements',
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

delete_option('sina_booking_settings');

// Remove roles
remove_role('sina_secretary');
remove_role('sina_doctor');
remove_role('sina_patient');

// Remove caps from admin
$admin = get_role('administrator');
if ($admin) {
    $admin->remove_cap('sina_manage_all');
    $admin->remove_cap('sina_manage_doctors');
    $admin->remove_cap('sina_manage_schedules');
    $admin->remove_cap('sina_manage_appointments');
    $admin->remove_cap('sina_manage_services');
    $admin->remove_cap('sina_manage_settings');
}

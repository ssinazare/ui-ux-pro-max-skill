<?php
if (!defined('ABSPATH')) exit;

class Sina_Booking_Roles {

    public static function add_roles() {
        // Secretary role
        add_role('sina_secretary', 'منشی پزشک', [
            'read' => true,
            'sina_manage_appointments' => true,
            'sina_view_doctor' => true,
        ]);

        // Doctor role
        add_role('sina_doctor', 'پزشک', [
            'read' => true,
            'sina_manage_appointments' => true,
            'sina_view_doctor' => true,
            'sina_manage_own_schedule' => true,
        ]);

        // Patient role (extends subscriber)
        add_role('sina_patient', 'بیمار', [
            'read' => true,
            'sina_book_appointment' => true,
        ]);
    }

    public static function add_caps() {
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('sina_manage_all');
            $admin->add_cap('sina_manage_doctors');
            $admin->add_cap('sina_manage_schedules');
            $admin->add_cap('sina_manage_appointments');
            $admin->add_cap('sina_manage_services');
            $admin->add_cap('sina_manage_settings');
        }

        // Editor also can manage
        $editor = get_role('editor');
        if ($editor) {
            $editor->add_cap('sina_manage_appointments');
        }
    }

    public static function current_user_can_manage($doctor_id = null) {
        if (current_user_can('sina_manage_all')) return true;
        if (current_user_can('sina_manage_appointments')) {
            if ($doctor_id) {
                $user_id = get_current_user_id();
                global $wpdb;
                $table = $wpdb->prefix . 'sina_doctors';
                $doc = $wpdb->get_row($wpdb->prepare("SELECT user_id, secretary_user_id FROM $table WHERE id = %d", $doctor_id));
                if ($doc) {
                    if ((int)$doc->user_id === $user_id) return true;
                    if ((int)$doc->secretary_user_id === $user_id) return true;
                }
                // For secretaries without specific assignment, allow if they have cap
                if (current_user_can('sina_secretary')) return true;
            } else {
                return true;
            }
        }
        return false;
    }

    public static function get_user_doctor_id($user_id = null) {
        if (!$user_id) $user_id = get_current_user_id();
        global $wpdb;
        $table = $wpdb->prefix . 'sina_doctors';
        $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE user_id = %d OR secretary_user_id = %d LIMIT 1", $user_id, $user_id));
        return $id ? intval($id) : null;
    }
}

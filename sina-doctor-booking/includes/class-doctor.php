<?php
if (!defined('ABSPATH')) exit;

class Sina_Booking_Doctor {

    public static function get_all($active_only = true) {
        global $wpdb;
        $table = $wpdb->prefix . 'sina_doctors';
        $where = $active_only ? 'WHERE is_active = 1' : '';
        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY name ASC");
    }

    public static function get_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'sina_doctors';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    public static function get_by_slug($slug) {
        global $wpdb;
        $table = $wpdb->prefix . 'sina_doctors';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE slug = %s", $slug));
    }

    public static function create_or_update($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'sina_doctors';

        $defaults = [
            'name' => '',
            'specialty' => '',
            'bio' => '',
            'avatar_url' => '',
            'phone' => '',
            'email' => '',
            'user_id' => null,
            'secretary_user_id' => null,
            'consultation_fee' => 0,
            'is_active' => 1,
            'announcement' => '',
        ];
        $data = wp_parse_args($data, $defaults);

        if (empty($data['name'])) return new WP_Error('no_name', 'نام پزشک الزامی است');

        if (empty($data['slug'])) {
            $data['slug'] = sanitize_title($data['name']);
        }

        // Check if update
        if (!empty($data['id'])) {
            $id = intval($data['id']);
            unset($data['id']);
            $wpdb->update($table, $data, ['id' => $id]);
            return $id;
        } else {
            // Ensure unique slug
            $orig_slug = $data['slug'];
            $i = 1;
            while ($wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE slug = %s", $data['slug']))) {
                $data['slug'] = $orig_slug . '-' . $i++;
            }
            $wpdb->insert($table, $data);
            return $wpdb->insert_id;
        }
    }

    public static function delete($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'sina_doctors';
        return $wpdb->delete($table, ['id' => $id]);
    }

    public static function get_services($doctor_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'sina_services';
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE doctor_id = %d AND is_active = 1 ORDER BY title ASC", $doctor_id));
    }

    public static function get_announcements($doctor_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'sina_announcements';
        $now = current_time('Y-m-d');
        
        if ($doctor_id) {
            $query = $wpdb->prepare(
                "SELECT * FROM $table WHERE is_active = 1 AND (doctor_id IS NULL OR doctor_id = %d) 
                AND (start_date IS NULL OR start_date <= %s) 
                AND (end_date IS NULL OR end_date >= %s)
                ORDER BY created_at DESC",
                $doctor_id, $now, $now
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT * FROM $table WHERE is_active = 1 
                AND (start_date IS NULL OR start_date <= %s) 
                AND (end_date IS NULL OR end_date >= %s)
                ORDER BY created_at DESC",
                $now, $now
            );
        }
        return $wpdb->get_results($query);
    }
}

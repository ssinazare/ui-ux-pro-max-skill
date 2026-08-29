<?php
if (!defined('ABSPATH')) exit;

class Sina_Booking_Ajax {

    public static function init() {
        // Public
        add_action('wp_ajax_sina_get_dates', [__CLASS__, 'get_dates']);
        add_action('wp_ajax_nopriv_sina_get_dates', [__CLASS__, 'get_dates']);

        add_action('wp_ajax_sina_get_slots', [__CLASS__, 'get_slots']);
        add_action('wp_ajax_nopriv_sina_get_slots', [__CLASS__, 'get_slots']);

        add_action('wp_ajax_sina_book_appointment', [__CLASS__, 'book_appointment']);
        add_action('wp_ajax_nopriv_sina_book_appointment', [__CLASS__, 'book_appointment']);

        add_action('wp_ajax_sina_get_appointment', [__CLASS__, 'get_appointment']);
        add_action('wp_ajax_nopriv_sina_get_appointment', [__CLASS__, 'get_appointment']);

        add_action('wp_ajax_sina_track', [__CLASS__, 'track']);
        add_action('wp_ajax_nopriv_sina_track', [__CLASS__, 'track']);

        // Admin / Secretary
        add_action('wp_ajax_sina_admin_update_status', [__CLASS__, 'admin_update_status']);
        add_action('wp_ajax_sina_admin_delete_appointment', [__CLASS__, 'admin_delete_appointment']);
        add_action('wp_ajax_sina_admin_calculate', [__CLASS__, 'admin_calculate']);
        add_action('wp_ajax_sina_admin_save_doctor', [__CLASS__, 'admin_save_doctor']);
        add_action('wp_ajax_sina_admin_delete_doctor', [__CLASS__, 'admin_delete_doctor']);
        add_action('wp_ajax_sina_admin_save_schedule', [__CLASS__, 'admin_save_schedule']);
        add_action('wp_ajax_sina_admin_delete_schedule', [__CLASS__, 'admin_delete_schedule']);
        add_action('wp_ajax_sina_admin_generate_slots', [__CLASS__, 'admin_generate_slots']);
        add_action('wp_ajax_sina_admin_block_slot', [__CLASS__, 'admin_block_slot']);
        add_action('wp_ajax_sina_admin_save_service', [__CLASS__, 'admin_save_service']);
        add_action('wp_ajax_sina_admin_delete_service', [__CLASS__, 'admin_delete_service']);
        add_action('wp_ajax_sina_admin_save_announcement', [__CLASS__, 'admin_save_announcement']);
        add_action('wp_ajax_sina_admin_delete_announcement', [__CLASS__, 'admin_delete_announcement']);
        add_action('wp_ajax_sina_admin_save_settings', [__CLASS__, 'admin_save_settings']);
    }

    private static function verify_nonce($action = 'sina_booking_nonce') {
        $nonce = $_REQUEST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, $action)) {
            wp_send_json_error(['message' => 'توکن امنیتی نامعتبر است']);
        }
    }

    public static function get_dates() {
        self::verify_nonce();
        $doctor_id = intval($_POST['doctor_id'] ?? 0);
        if (!$doctor_id) wp_send_json_error(['message' => 'پزشک انتخاب نشده']);

        $dates = Sina_Booking_Schedule::get_available_dates($doctor_id, 30);
        
        // Format for frontend
        $formatted = array_map(function($d){
            $j = Sina_Booking_Schedule::format_jalali($d->slot_date);
            $day_name = Sina_Booking_Schedule::persian_day_name(date('w', strtotime($d->slot_date)) == 6 ? 0 : date('w', strtotime($d->slot_date)) + 1);
            // Simpler: use date
            $dt = new DateTime($d->slot_date);
            $dow = $dt->format('w');
            $map = [0 => 1, 1 => 2, 2 => 3, 3 => 4, 4 => 5, 5 => 6, 6 => 0];
            $pdow = $map[(int)$dow];
            $day_name = Sina_Booking_Schedule::persian_day_name($pdow);
            
            return [
                'date' => $d->slot_date,
                'jalali' => $j,
                'day_name' => $day_name,
                'free' => intval($d->free_count),
                'total' => intval($d->total),
            ];
        }, $dates);

        wp_send_json_success(['dates' => $formatted]);
    }

    public static function get_slots() {
        self::verify_nonce();
        $doctor_id = intval($_POST['doctor_id'] ?? 0);
        $date = sanitize_text_field($_POST['date'] ?? '');
        if (!$doctor_id || !$date) wp_send_json_error(['message' => 'اطلاعات ناقص']);

        $slots = Sina_Booking_Schedule::get_slots_for_date($doctor_id, $date);
        $formatted = array_map(function($s){
            return [
                'id' => $s->id,
                'time' => substr($s->slot_time, 0, 5),
                'is_booked' => (bool)$s->is_booked,
                'is_blocked' => (bool)$s->is_blocked,
                'blocked_reason' => $s->blocked_reason,
            ];
        }, $slots);

        wp_send_json_success(['slots' => $formatted]);
    }

    public static function book_appointment() {
        self::verify_nonce();
        
        $data = [
            'slot_id' => intval($_POST['slot_id'] ?? 0),
            'patient_name' => sanitize_text_field($_POST['patient_name'] ?? ''),
            'patient_phone' => sanitize_text_field($_POST['patient_phone'] ?? ''),
            'patient_national_id' => sanitize_text_field($_POST['patient_national_id'] ?? ''),
            'patient_email' => sanitize_email($_POST['patient_email'] ?? ''),
            'patient_age' => intval($_POST['patient_age'] ?? 0),
            'patient_gender' => sanitize_text_field($_POST['patient_gender'] ?? ''),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
        ];

        $result = Sina_Booking_Appointment::book($data);
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        // If WooCommerce enabled, return order checkout URL
        $settings = get_option('sina_booking_settings', []);
        $checkout_url = '';
        if (!empty($settings['enable_woocommerce']) && !empty($result['appointment_id'])) {
            $appointment = Sina_Booking_Appointment::get_by_id($result['appointment_id']);
            if ($appointment && $appointment->woo_order_id) {
                $order = wc_get_order($appointment->woo_order_id);
                if ($order) $checkout_url = $order->get_checkout_payment_url();
            }
        }

        wp_send_json_success([
            'message' => 'نوبت با موفقیت ثبت شد',
            'tracking_code' => $result['tracking_code'],
            'appointment_id' => $result['appointment_id'],
            'checkout_url' => $checkout_url,
            'print_url' => home_url('/sina-print/' . $result['tracking_code'] . '/'),
        ]);
    }

    public static function get_appointment() {
        self::verify_nonce();
        $code = sanitize_text_field($_POST['tracking_code'] ?? '');
        if (!$code) wp_send_json_error(['message' => 'کد رهگیری وارد نشده']);

        $appointment = Sina_Booking_Appointment::get_by_tracking_code($code);
        if (!$appointment) wp_send_json_error(['message' => 'نوبت یافت نشد']);

        $doctor = Sina_Booking_Doctor::get_by_id($appointment->doctor_id);
        
        wp_send_json_success([
            'appointment' => [
                'tracking_code' => $appointment->tracking_code,
                'doctor_name' => $doctor ? $doctor->name : '',
                'doctor_specialty' => $doctor ? $doctor->specialty : '',
                'date' => $appointment->slot_date,
                'jalali' => Sina_Booking_Schedule::format_jalali($appointment->slot_date),
                'time' => substr($appointment->slot_time, 0, 5),
                'patient_name' => $appointment->patient_name,
                'status' => $appointment->status,
                'total_fee' => $appointment->total_fee,
            ]
        ]);
    }

    public static function track() {
        self::verify_nonce('sina_booking_nonce');
        $code = sanitize_text_field($_POST['code'] ?? '');
        $appointment = Sina_Booking_Appointment::get_by_tracking_code($code);
        if (!$appointment) wp_send_json_error(['message' => 'کد رهگیری نامعتبر است']);
        
        $doctor = Sina_Booking_Doctor::get_by_id($appointment->doctor_id);
        ob_start();
        include SINA_BOOKING_PATH . 'public/views/tracking-result.php';
        $html = ob_get_clean();
        wp_send_json_success(['html' => $html]);
    }

    // Admin handlers
    public static function admin_update_status() {
        check_ajax_referer('sina_booking_admin_nonce', 'nonce');
        if (!current_user_can('sina_manage_appointments') && !current_user_can('sina_manage_all')) {
            wp_send_json_error(['message' => 'دسترسی غیر مجاز']);
        }

        $id = intval($_POST['id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');
        $total_fee = intval($_POST['total_fee'] ?? 0);
        $paid_amount = intval($_POST['paid_amount'] ?? 0);
        $payment_status = sanitize_text_field($_POST['payment_status'] ?? '');
        $secretary_notes = sanitize_textarea_field($_POST['secretary_notes'] ?? '');
        $services = json_decode(stripslashes($_POST['services'] ?? '[]'), true);
        $visit_date = sanitize_text_field($_POST['visit_date'] ?? '');

        $extra = [
            'secretary_notes' => $secretary_notes,
            'total_fee' => $total_fee,
            'paid_amount' => $paid_amount,
            'payment_status' => $payment_status,
            'services' => $services,
            'visit_date' => $visit_date ?: null,
        ];

        $result = Sina_Booking_Appointment::update_status($id, $status, $extra);
        if ($result !== false) {
            wp_send_json_success(['message' => 'وضعیت بروزرسانی شد']);
        } else {
            wp_send_json_error(['message' => 'خطا در بروزرسانی']);
        }
    }

    public static function admin_delete_appointment() {
        check_ajax_referer('sina_booking_admin_nonce', 'nonce');
        if (!current_user_can('sina_manage_appointments') && !current_user_can('sina_manage_all')) {
            wp_send_json_error(['message' => 'دسترسی غیر مجاز']);
        }
        $id = intval($_POST['id'] ?? 0);
        $result = Sina_Booking_Appointment::delete($id);
        if ($result) wp_send_json_success(['message' => 'حذف شد']);
        else wp_send_json_error(['message' => 'خطا']);
    }

    public static function admin_calculate() {
        check_ajax_referer('sina_booking_admin_nonce', 'nonce');
        $id = intval($_POST['id'] ?? 0);
        $services = json_decode(stripslashes($_POST['services'] ?? '[]'), true);
        $service_ids = array_map('intval', $services);
        $total = Sina_Booking_Appointment::calculate_total($id, $service_ids);
        wp_send_json_success(['total' => $total, 'formatted' => number_format($total) . ' تومان']);
    }

    public static function admin_save_doctor() {
        check_ajax_referer('sina_booking_admin_nonce', 'nonce');
        if (!current_user_can('sina_manage_doctors') && !current_user_can('sina_manage_all')) {
            wp_send_json_error(['message' => 'دسترسی غیر مجاز']);
        }

        $data = [
            'id' => intval($_POST['id'] ?? 0) ?: null,
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'specialty' => sanitize_text_field($_POST['specialty'] ?? ''),
            'bio' => sanitize_textarea_field($_POST['bio'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'consultation_fee' => intval($_POST['consultation_fee'] ?? 0),
            'is_active' => intval($_POST['is_active'] ?? 1),
            'announcement' => sanitize_textarea_field($_POST['announcement'] ?? ''),
            'user_id' => intval($_POST['user_id'] ?? 0) ?: null,
            'secretary_user_id' => intval($_POST['secretary_user_id'] ?? 0) ?: null,
            'avatar_url' => esc_url_raw($_POST['avatar_url'] ?? ''),
        ];

        $result = Sina_Booking_Doctor::create_or_update($data);
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        wp_send_json_success(['message' => 'پزشک ذخیره شد', 'id' => $result]);
    }

    public static function admin_delete_doctor() {
        check_ajax_referer('sina_booking_admin_nonce', 'nonce');
        if (!current_user_can('sina_manage_doctors') && !current_user_can('sina_manage_all')) {
            wp_send_json_error(['message' => 'دسترسی غیر مجاز']);
        }
        $id = intval($_POST['id'] ?? 0);
        Sina_Booking_Doctor::delete($id);
        wp_send_json_success(['message' => 'حذف شد']);
    }

    public static function admin_save_schedule() {
        check_ajax_referer('sina_booking_admin_nonce', 'nonce');
        if (!current_user_can('sina_manage_schedules') && !current_user_can('sina_manage_all')) {
            wp_send_json_error(['message' => 'دسترسی غیر مجاز']);
        }
        $data = [
            'id' => intval($_POST['id'] ?? 0) ?: null,
            'doctor_id' => intval($_POST['doctor_id'] ?? 0),
            'day_of_week' => intval($_POST['day_of_week'] ?? 0),
            'date_specific' => !empty($_POST['date_specific']) ? sanitize_text_field($_POST['date_specific']) : null,
            'start_time' => sanitize_text_field($_POST['start_time'] ?? '09:00:00'),
            'end_time' => sanitize_text_field($_POST['end_time'] ?? '13:00:00'),
            'slot_duration' => intval($_POST['slot_duration'] ?? 15),
            'is_active' => intval($_POST['is_active'] ?? 1),
            'announcement' => sanitize_textarea_field($_POST['announcement'] ?? ''),
        ];
        $id = Sina_Booking_Schedule::save_schedule($data);
        wp_send_json_success(['message' => 'برنامه ذخیره شد', 'id' => $id]);
    }

    public static function admin_delete_schedule() {
        check_ajax_referer('sina_booking_admin_nonce', 'nonce');
        $id = intval($_POST['id'] ?? 0);
        Sina_Booking_Schedule::delete_schedule($id);
        wp_send_json_success(['message' => 'حذف شد']);
    }

    public static function admin_generate_slots() {
        check_ajax_referer('sina_booking_admin_nonce', 'nonce');
        $doctor_id = intval($_POST['doctor_id'] ?? 0);
        $days = intval($_POST['days'] ?? 30);
        if ($doctor_id) {
            $count = Sina_Booking_Schedule::generate_slots($doctor_id, $days);
        } else {
            $count = Sina_Booking_Schedule::generate_slots_for_all_doctors($days);
        }
        wp_send_json_success(['message' => "$count اسلات جدید تولید شد", 'count' => $count]);
    }

    public static function admin_block_slot() {
        check_ajax_referer('sina_booking_admin_nonce', 'nonce');
        $slot_id = intval($_POST['slot_id'] ?? 0);
        $action = sanitize_text_field($_POST['block_action'] ?? 'block');
        $reason = sanitize_text_field($_POST['reason'] ?? '');
        
        if ($action === 'block') {
            Sina_Booking_Schedule::block_slot($slot_id, $reason);
        } else {
            Sina_Booking_Schedule::unblock_slot($slot_id);
        }
        wp_send_json_success(['message' => 'انجام شد']);
    }

    public static function admin_save_service() {
        check_ajax_referer('sina_booking_admin_nonce', 'nonce');
        global $wpdb;
        $table = $wpdb->prefix . 'sina_services';
        $data = [
            'doctor_id' => intval($_POST['doctor_id'] ?? 0),
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'price' => intval($_POST['price'] ?? 0),
            'duration' => intval($_POST['duration'] ?? 0),
            'is_active' => intval($_POST['is_active'] ?? 1),
        ];
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $wpdb->update($table, $data, ['id' => $id]);
        } else {
            $wpdb->insert($table, $data);
            $id = $wpdb->insert_id;
        }
        wp_send_json_success(['message' => 'خدمت ذخیره شد', 'id' => $id]);
    }

    public static function admin_delete_service() {
        check_ajax_referer('sina_booking_admin_nonce', 'nonce');
        global $wpdb;
        $table = $wpdb->prefix . 'sina_services';
        $id = intval($_POST['id'] ?? 0);
        $wpdb->delete($table, ['id' => $id]);
        wp_send_json_success(['message' => 'حذف شد']);
    }

    public static function admin_save_announcement() {
        check_ajax_referer('sina_booking_admin_nonce', 'nonce');
        global $wpdb;
        $table = $wpdb->prefix . 'sina_announcements';
        $data = [
            'doctor_id' => intval($_POST['doctor_id'] ?? 0) ?: null,
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'message' => sanitize_textarea_field($_POST['message'] ?? ''),
            'type' => sanitize_text_field($_POST['type'] ?? 'info'),
            'start_date' => !empty($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null,
            'end_date' => !empty($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null,
            'is_active' => intval($_POST['is_active'] ?? 1),
        ];
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $wpdb->update($table, $data, ['id' => $id]);
        } else {
            $wpdb->insert($table, $data);
            $id = $wpdb->insert_id;
        }
        wp_send_json_success(['message' => 'اطلاعیه ذخیره شد', 'id' => $id]);
    }

    public static function admin_delete_announcement() {
        check_ajax_referer('sina_booking_admin_nonce', 'nonce');
        global $wpdb;
        $table = $wpdb->prefix . 'sina_announcements';
        $id = intval($_POST['id'] ?? 0);
        $wpdb->delete($table, ['id' => $id]);
        wp_send_json_success(['message' => 'حذف شد']);
    }

    public static function admin_save_settings() {
        check_ajax_referer('sina_booking_admin_nonce', 'nonce');
        if (!current_user_can('sina_manage_settings') && !current_user_can('sina_manage_all')) {
            wp_send_json_error(['message' => 'دسترسی غیر مجاز']);
        }
        $settings = [
            'booking_fee' => intval($_POST['booking_fee'] ?? 5000),
            'currency' => sanitize_text_field($_POST['currency'] ?? 'تومان'),
            'enable_woocommerce' => intval($_POST['enable_woocommerce'] ?? 0),
            'thermal_width' => sanitize_text_field($_POST['thermal_width'] ?? '80mm'),
            'clinic_name' => sanitize_text_field($_POST['clinic_name'] ?? ''),
            'clinic_address' => sanitize_text_field($_POST['clinic_address'] ?? ''),
            'clinic_phone' => sanitize_text_field($_POST['clinic_phone'] ?? ''),
        ];
        update_option('sina_booking_settings', $settings);
        wp_send_json_success(['message' => 'تنظیمات ذخیره شد']);
    }
}

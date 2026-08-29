<?php
if (!defined('ABSPATH')) exit;

class Sina_Booking_Appointment {

    public static function generate_tracking_code() {
        $prefix = 'SINA-';
        $code = $prefix . strtoupper(wp_generate_password(8, false, false));
        // Ensure unique
        global $wpdb;
        $table = $wpdb->prefix . 'sina_appointments';
        while ($wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE tracking_code = %s", $code))) {
            $code = $prefix . strtoupper(wp_generate_password(8, false, false));
        }
        return $code;
    }

    public static function book($data) {
        global $wpdb;
        $table_appointments = $wpdb->prefix . 'sina_appointments';
        $table_slots = $wpdb->prefix . 'sina_slots';

        // Validate slot
        $slot_id = intval($data['slot_id'] ?? 0);
        if (!$slot_id) return new WP_Error('no_slot', 'اسلات انتخاب نشده');

        $slot = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_slots WHERE id = %d", $slot_id));
        if (!$slot) return new WP_Error('invalid_slot', 'نوبت نامعتبر است');
        if ($slot->is_booked) return new WP_Error('booked', 'این نوبت قبلا رزرو شده است');
        if ($slot->is_blocked) return new WP_Error('blocked', 'این نوبت مسدود است: ' . $slot->blocked_reason);

        // Validate patient data
        if (empty($data['patient_name']) || empty($data['patient_phone'])) {
            return new WP_Error('missing_data', 'نام و شماره تماس الزامی است');
        }

        $settings = get_option('sina_booking_settings', []);
        $booking_fee = $settings['booking_fee'] ?? 5000;

        $doctor = Sina_Booking_Doctor::get_by_id($slot->doctor_id);
        $total_fee = $doctor ? $doctor->consultation_fee : 0;

        $tracking_code = self::generate_tracking_code();

        $insert_data = [
            'tracking_code' => $tracking_code,
            'doctor_id' => $slot->doctor_id,
            'slot_id' => $slot->id,
            'slot_date' => $slot->slot_date,
            'slot_time' => $slot->slot_time,
            'patient_user_id' => get_current_user_id() ?: null,
            'patient_name' => sanitize_text_field($data['patient_name']),
            'patient_phone' => sanitize_text_field($data['patient_phone']),
            'patient_national_id' => sanitize_text_field($data['patient_national_id'] ?? ''),
            'patient_email' => sanitize_email($data['patient_email'] ?? ''),
            'patient_age' => intval($data['patient_age'] ?? 0),
            'patient_gender' => sanitize_text_field($data['patient_gender'] ?? ''),
            'booking_fee' => $booking_fee,
            'total_fee' => $total_fee,
            'paid_amount' => 0,
            'services' => json_encode([]),
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
        ];

        $result = $wpdb->insert($table_appointments, $insert_data);
        if (!$result) {
            return new WP_Error('db_error', 'خطا در ثبت نوبت');
        }

        $appointment_id = $wpdb->insert_id;

        // Mark slot as booked
        $wpdb->update($table_slots, ['is_booked' => 1], ['id' => $slot->id]);

        // Handle WooCommerce if enabled
        $settings = get_option('sina_booking_settings', []);
        if (!empty($settings['enable_woocommerce']) && class_exists('WooCommerce')) {
            $order_id = Sina_Booking_WooCommerce::create_order($appointment_id);
            if ($order_id) {
                $wpdb->update($table_appointments, ['woo_order_id' => $order_id], ['id' => $appointment_id]);
            }
        }

        // Send SMS? Hook
        do_action('sina_booking_after_book', $appointment_id);

        return [
            'appointment_id' => $appointment_id,
            'tracking_code' => $tracking_code,
            'slot_date' => $slot->slot_date,
            'slot_time' => $slot->slot_time,
        ];
    }

    public static function get_by_tracking_code($code) {
        global $wpdb;
        $table = $wpdb->prefix . 'sina_appointments';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE tracking_code = %s", $code));
    }

    public static function get_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'sina_appointments';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    public static function get_appointments($args = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'sina_appointments';

        $defaults = [
            'doctor_id' => null,
            'status' => null,
            'date_from' => null,
            'date_to' => null,
            'search' => null,
            'orderby' => 'slot_date',
            'order' => 'ASC',
            'limit' => 50,
            'offset' => 0,
            'patient_user_id' => null,
        ];
        $args = wp_parse_args($args, $defaults);

        $where = ['1=1'];
        $values = [];

        if ($args['doctor_id']) {
            $where[] = 'doctor_id = %d';
            $values[] = $args['doctor_id'];
        }
        if ($args['status']) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }
        if ($args['date_from']) {
            $where[] = 'slot_date >= %s';
            $values[] = $args['date_from'];
        }
        if ($args['date_to']) {
            $where[] = 'slot_date <= %s';
            $values[] = $args['date_to'];
        }
        if ($args['search']) {
            $where[] = '(patient_name LIKE %s OR patient_phone LIKE %s OR tracking_code LIKE %s)';
            $like = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
        }
        if ($args['patient_user_id']) {
            $where[] = 'patient_user_id = %d';
            $values[] = $args['patient_user_id'];
        }

        $where_sql = implode(' AND ', $where);
        $orderby = in_array($args['orderby'], ['slot_date', 'slot_time', 'created_at', 'patient_name', 'id']) ? $args['orderby'] : 'slot_date';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';

        $query = "SELECT * FROM $table WHERE $where_sql ORDER BY $orderby $order, slot_time $order LIMIT %d OFFSET %d";
        $values[] = $args['limit'];
        $values[] = $args['offset'];

        if (!empty($values)) {
            $query = $wpdb->prepare($query, $values);
        }

        return $wpdb->get_results($query);
    }

    public static function count_appointments($args = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'sina_appointments';
        $defaults = [
            'doctor_id' => null,
            'status' => null,
            'date_from' => null,
            'date_to' => null,
            'search' => null,
        ];
        $args = wp_parse_args($args, $defaults);

        $where = ['1=1'];
        $values = [];

        if ($args['doctor_id']) {
            $where[] = 'doctor_id = %d';
            $values[] = $args['doctor_id'];
        }
        if ($args['status']) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }
        if ($args['date_from']) {
            $where[] = 'slot_date >= %s';
            $values[] = $args['date_from'];
        }
        if ($args['date_to']) {
            $where[] = 'slot_date <= %s';
            $values[] = $args['date_to'];
        }
        if ($args['search']) {
            $where[] = '(patient_name LIKE %s OR patient_phone LIKE %s OR tracking_code LIKE %s)';
            $like = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
        }

        $where_sql = implode(' AND ', $where);
        $query = "SELECT COUNT(*) FROM $table WHERE $where_sql";
        if (!empty($values)) {
            $query = $wpdb->prepare($query, $values);
        }
        return (int)$wpdb->get_var($query);
    }

    public static function update_status($appointment_id, $status, $extra = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'sina_appointments';
        
        $data = ['status' => $status];
        if (isset($extra['secretary_notes'])) $data['secretary_notes'] = $extra['secretary_notes'];
        if (isset($extra['total_fee'])) $data['total_fee'] = $extra['total_fee'];
        if (isset($extra['paid_amount'])) $data['paid_amount'] = $extra['paid_amount'];
        if (isset($extra['payment_status'])) $data['payment_status'] = $extra['payment_status'];
        if (isset($extra['services'])) $data['services'] = json_encode($extra['services']);
        if (isset($extra['visit_date'])) $data['visit_date'] = $extra['visit_date'];
        if ($status === 'visited' && empty($data['visit_date'])) {
            $data['visit_date'] = current_time('mysql');
        }

        return $wpdb->update($table, $data, ['id' => $appointment_id]);
    }

    public static function delete($appointment_id) {
        global $wpdb;
        $table_appointments = $wpdb->prefix . 'sina_appointments';
        $table_slots = $wpdb->prefix . 'sina_slots';

        $appointment = self::get_by_id($appointment_id);
        if (!$appointment) return false;

        // Free slot
        if ($appointment->slot_id) {
            $wpdb->update($table_slots, ['is_booked' => 0], ['id' => $appointment->slot_id]);
        }

        return $wpdb->delete($table_appointments, ['id' => $appointment_id]);
    }

    public static function calculate_total($appointment_id, $service_ids = []) {
        global $wpdb;
        $appointment = self::get_by_id($appointment_id);
        if (!$appointment) return 0;

        $doctor = Sina_Booking_Doctor::get_by_id($appointment->doctor_id);
        $base_fee = $doctor ? $doctor->consultation_fee : 0;

        $table_services = $wpdb->prefix . 'sina_services';
        $services_total = 0;
        $services_data = [];

        if (!empty($service_ids)) {
            foreach ($service_ids as $sid) {
                $service = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_services WHERE id = %d", $sid));
                if ($service) {
                    $services_total += $service->price;
                    $services_data[] = [
                        'id' => $service->id,
                        'title' => $service->title,
                        'price' => $service->price,
                    ];
                }
            }
        }

        $total = $base_fee + $services_total;

        // Update appointment
        $wpdb->update(
            $wpdb->prefix . 'sina_appointments',
            [
                'total_fee' => $total,
                'services' => json_encode($services_data),
            ],
            ['id' => $appointment_id]
        );

        return $total;
    }

    public static function get_patient_appointments($user_id = null) {
        if (!$user_id) $user_id = get_current_user_id();
        if (!$user_id) return [];

        return self::get_appointments([
            'patient_user_id' => $user_id,
            'limit' => 100,
            'orderby' => 'slot_date',
            'order' => 'DESC',
        ]);
    }
}

<?php
if (!defined('ABSPATH')) exit;

class Sina_Booking_WooCommerce {

    public static function init() {
        add_action('init', [__CLASS__, 'check_woo']);
    }

    public static function check_woo() {
        if (!class_exists('WooCommerce')) return;

        // Add custom product type? We'll use simple product with meta
        add_action('woocommerce_order_status_completed', [__CLASS__, 'order_completed']);
        add_action('woocommerce_order_status_processing', [__CLASS__, 'order_completed']);
        add_action('woocommerce_thankyou', [__CLASS__, 'thankyou_page']);

        // Filter to show tracking code in order
        add_action('woocommerce_admin_order_data_after_billing_address', [__CLASS__, 'show_in_admin_order']);
    }

    public static function create_order($appointment_id) {
        if (!class_exists('WooCommerce')) return false;
        if (!function_exists('wc_create_order')) return false;

        $appointment = Sina_Booking_Appointment::get_by_id($appointment_id);
        if (!$appointment) return false;

        $doctor = Sina_Booking_Doctor::get_by_id($appointment->doctor_id);
        $settings = get_option('sina_booking_settings', []);
        $booking_fee = $settings['booking_fee'] ?? 5000;

        try {
            $order = wc_create_order();
            $order->set_customer_id($appointment->patient_user_id ?: 0);
            
            // Add booking fee as product
            $product_name = 'رزرو نوبت - ' . ($doctor ? $doctor->name : '') . ' - ' . $appointment->slot_date . ' ' . $appointment->slot_time;
            
            // Create a fee line instead of product to avoid needing product
            $item = new WC_Order_Item_Fee();
            $item->set_name($product_name);
            $item->set_amount($booking_fee);
            $item->set_total($booking_fee);
            $item->set_tax_status('none');
            $order->add_item($item);

            // Set billing
            $order->set_billing_first_name($appointment->patient_name);
            $order->set_billing_phone($appointment->patient_phone);
            if ($appointment->patient_email) {
                $order->set_billing_email($appointment->patient_email);
            }

            // Meta
            $order->update_meta_data('_sina_appointment_id', $appointment_id);
            $order->update_meta_data('_sina_tracking_code', $appointment->tracking_code);

            $order->calculate_totals();
            $order->update_status('pending', 'سفارش رزرو نوبت ایجاد شد');

            return $order->get_id();

        } catch (Exception $e) {
            error_log('Sina Booking Woo Error: ' . $e->getMessage());
            return false;
        }
    }

    public static function order_completed($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $appointment_id = $order->get_meta('_sina_appointment_id');
        if (!$appointment_id) return;

        global $wpdb;
        $table = $wpdb->prefix . 'sina_appointments';
        
        $wpdb->update($table, [
            'payment_status' => 'paid',
            'paid_amount' => $order->get_total(),
            'status' => 'confirmed',
        ], ['id' => $appointment_id]);
    }

    public static function thankyou_page($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        $tracking = $order->get_meta('_sina_tracking_code');
        if ($tracking) {
            echo '<div class="sina-thankyou-box" style="background:#f0fdf4;border:2px solid #22c55e;padding:20px;border-radius:12px;margin:20px 0;">';
            echo '<h3 style="color:#15803d;margin:0 0 10px;">✅ نوبت شما تایید شد</h3>';
            echo '<p>کد رهگیری: <strong style="font-size:18px;letter-spacing:1px;">' . esc_html($tracking) . '</strong></p>';
            echo '<p><a href="' . esc_url(home_url('/sina-print/' . $tracking . '/')) . '" target="_blank" style="background:#22c55e;color:white;padding:10px 20px;border-radius:8px;text-decoration:none;display:inline-block;margin-top:10px;">🖨️ چاپ رسید</a></p>';
            echo '</div>';
        }
    }

    public static function show_in_admin_order($order) {
        $tracking = $order->get_meta('_sina_tracking_code');
        $appointment_id = $order->get_meta('_sina_appointment_id');
        if ($tracking) {
            echo '<p><strong>کد رهگیری نوبت:</strong> ' . esc_html($tracking) . '</p>';
            if ($appointment_id) {
                echo '<p><a href="' . admin_url('admin.php?page=sina-booking-appointments&tracking=' . $tracking) . '">مشاهده نوبت</a></p>';
            }
        }
    }

    public static function is_enabled() {
        $settings = get_option('sina_booking_settings', []);
        return !empty($settings['enable_woocommerce']) && class_exists('WooCommerce');
    }
}

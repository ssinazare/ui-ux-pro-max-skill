<?php
if (!defined('ABSPATH')) exit;

class Sina_Booking_Shortcodes {

    public static function init() {
        add_shortcode('sina_booking', [__CLASS__, 'booking_shortcode']);
        add_shortcode('sina_doctors_list', [__CLASS__, 'doctors_list_shortcode']);
        add_shortcode('sina_patient_panel', [__CLASS__, 'patient_panel_shortcode']);
        add_shortcode('sina_secretary_panel', [__CLASS__, 'secretary_panel_shortcode']);
        add_shortcode('sina_tracking', [__CLASS__, 'tracking_shortcode']);
    }

    public static function booking_shortcode($atts) {
        $atts = shortcode_atts([
            'doctor_id' => 0,
            'style' => 'modern',
        ], $atts, 'sina_booking');

        ob_start();
        include SINA_BOOKING_PATH . 'public/views/booking-flow.php';
        return ob_get_clean();
    }

    public static function doctors_list_shortcode($atts) {
        $atts = shortcode_atts([
            'columns' => 3,
            'show_specialty' => 'yes',
        ], $atts, 'sina_doctors_list');

        $doctors = Sina_Booking_Doctor::get_all(true);
        ob_start();
        include SINA_BOOKING_PATH . 'public/views/doctors-list.php';
        return ob_get_clean();
    }

    public static function patient_panel_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="sina-notice sina-notice-warning">برای مشاهده پنل بیمار لطفا وارد حساب کاربری خود شوید. <a href="'.wp_login_url(get_permalink()).'">ورود</a></div>';
        }

        $appointments = Sina_Booking_Appointment::get_patient_appointments();
        ob_start();
        include SINA_BOOKING_PATH . 'public/views/patient-panel.php';
        return ob_get_clean();
    }

    public static function secretary_panel_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="sina-notice sina-notice-warning">برای دسترسی به پنل منشی لطفا وارد شوید.</div>';
        }

        if (!current_user_can('sina_manage_appointments') && !current_user_can('sina_manage_all') && !current_user_can('sina_secretary')) {
            return '<div class="sina-notice sina-notice-danger">شما دسترسی به این بخش را ندارید.</div>';
        }

        $doctor_id = Sina_Booking_Roles::get_user_doctor_id();
        if (!$doctor_id && !current_user_can('sina_manage_all')) {
            // Secretary without assignment sees all? Or message
            $doctor_id = null;
        }

        ob_start();
        include SINA_BOOKING_PATH . 'public/views/secretary-panel.php';
        return ob_get_clean();
    }

    public static function tracking_shortcode($atts) {
        $atts = shortcode_atts([
            'placeholder' => 'کد رهگیری را وارد کنید (مثال: SINA-XXXXXX)',
        ], $atts, 'sina_tracking');

        ob_start();
        ?>
        <div class="sina-tracking-form" id="sinaTrackingForm">
            <div class="sina-tracking-input-wrap">
                <input type="text" id="sinaTrackingCode" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" class="sina-input">
                <button type="button" id="sinaTrackingBtn" class="sina-btn sina-btn-primary">پیگیری نوبت</button>
            </div>
            <div id="sinaTrackingResult" class="sina-tracking-result" style="display:none;"></div>
        </div>
        <script>
        jQuery(document).ready(function($){
            $('#sinaTrackingBtn').on('click', function(){
                var code = $('#sinaTrackingCode').val().trim();
                if(!code) { alert('لطفا کد رهگیری را وارد کنید'); return; }
                var $btn = $(this);
                $btn.prop('disabled', true).text('در حال جستجو...');
                $.post(sinaBooking.ajax_url, {
                    action: 'sina_track',
                    code: code,
                    nonce: sinaBooking.nonce
                }, function(res){
                    $btn.prop('disabled', false).text('پیگیری نوبت');
                    if(res.success) {
                        $('#sinaTrackingResult').html(res.data.html).show();
                    } else {
                        $('#sinaTrackingResult').html('<div class="sina-alert sina-alert-danger">'+res.data.message+'</div>').show();
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

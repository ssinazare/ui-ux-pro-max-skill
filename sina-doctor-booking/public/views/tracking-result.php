<?php if (!defined('ABSPATH')) exit;
?>
<div class="sina-appointment-card" style="border:2px solid #0ea5e9; background:#f0f9ff;">
    <div>
        <h4 style="margin:0 0 8px;">🔖 کد رهگیری: <?php echo esc_html($appointment->tracking_code); ?></h4>
        <div class="sina-appointment-meta">
            <span>👨‍⚕️ <?php echo esc_html($doctor ? $doctor->name : ''); ?></span>
            <span>📅 <?php echo esc_html(Sina_Booking_Schedule::format_jalali($appointment->slot_date)); ?></span>
            <span>⏰ <?php echo esc_html(substr($appointment->slot_time,0,5)); ?></span>
        </div>
        <div style="margin-top:8px;">
            <span class="sina-status-badge sina-status-<?php echo esc_attr($appointment->status); ?>">
                <?php
                $labels = ['pending'=>'در انتظار','confirmed'=>'تایید شده','visited'=>'ویزیت شده','cancelled'=>'لغو شده'];
                echo esc_html($labels[$appointment->status] ?? $appointment->status);
                ?>
            </span>
        </div>
    </div>
    <div>
        <a href="<?php echo home_url('/sina-print/'.$appointment->tracking_code.'/'); ?>" target="_blank" class="sina-btn sina-btn-primary">🖨️ چاپ رسید</a>
    </div>
</div>

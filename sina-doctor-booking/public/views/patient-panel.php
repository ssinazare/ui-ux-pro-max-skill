<?php if (!defined('ABSPATH')) exit;
$settings = get_option('sina_booking_settings', []);
?>
<div class="sina-booking-wrapper">
    <div class="sina-section">
        <h2 class="sina-section-title">👤 پنل بیمار - نوبت‌های من</h2>
        <p>سلام <strong><?php echo esc_html(wp_get_current_user()->display_name); ?></strong> عزیز، لیست نوبت‌های شما در زیر نمایش داده می‌شود</p>
    </div>

    <?php if (empty($appointments)): ?>
        <div class="sina-alert sina-alert-info">
            شما تاکنون نوبتی ثبت نکرده‌اید. <a href="<?php echo home_url('/sina-booking/'); ?>">رزرو نوبت جدید</a>
        </div>
    <?php else: ?>
        <div class="sina-appointments-list">
            <?php foreach ($appointments as $app):
                $doctor = Sina_Booking_Doctor::get_by_id($app->doctor_id);
                $jalali = Sina_Booking_Schedule::format_jalali($app->slot_date);
            ?>
            <div class="sina-appointment-card">
                <div class="sina-appointment-info">
                    <h4><?php echo esc_html($doctor ? $doctor->name : 'پزشک'); ?> - <?php echo esc_html($doctor ? $doctor->specialty : ''); ?></h4>
                    <div class="sina-appointment-meta">
                        <span>📅 <?php echo esc_html($jalali); ?> (<?php echo esc_html($app->slot_date); ?>)</span>
                        <span>⏰ <?php echo esc_html(substr($app->slot_time,0,5)); ?></span>
                        <span>🔖 <?php echo esc_html($app->tracking_code); ?></span>
                        <span>💰 <?php echo number_format($app->total_fee); ?> تومان</span>
                    </div>
                    <?php if ($app->secretary_notes): ?>
                    <div style="margin-top:8px; font-size:13px; background:#f8fafc; padding:8px; border-radius:8px;">
                        <strong>یادداشت منشی:</strong> <?php echo esc_html($app->secretary_notes); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div style="display:flex; flex-direction:column; gap:8px; align-items:flex-end;">
                    <span class="sina-status-badge sina-status-<?php echo esc_attr($app->status); ?>">
                        <?php
                        $status_labels = ['pending'=>'در انتظار','confirmed'=>'تایید شده','visited'=>'ویزیت شده','cancelled'=>'لغو شده','no_show'=>'عدم مراجعه'];
                        echo esc_html($status_labels[$app->status] ?? $app->status);
                        ?>
                    </span>
                    <div style="display:flex; gap:8px;">
                        <a href="<?php echo home_url('/sina-print/'.$app->tracking_code.'/'); ?>" target="_blank" class="sina-btn sina-btn-secondary" style="font-size:12px; padding:6px 12px;">🖨️ چاپ</a>
                        <?php if ($app->status === 'pending'): ?>
                        <button class="sina-btn sina-btn-danger sina-cancel-appointment" data-id="<?php echo esc_attr($app->id); ?>" data-code="<?php echo esc_attr($app->tracking_code); ?>" style="font-size:12px; padding:6px 12px;">لغو</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="sina-section sina-text-center">
        <a href="<?php echo home_url('/sina-booking/'); ?>" class="sina-btn sina-btn-primary">➕ رزرو نوبت جدید</a>
    </div>
</div>

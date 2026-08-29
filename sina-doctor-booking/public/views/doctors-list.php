<?php if (!defined('ABSPATH')) exit;
$columns = $atts['columns'] ?? 3;
$show_specialty = ($atts['show_specialty'] ?? 'yes') === 'yes';
?>
<div class="sina-booking-wrapper">
    <?php
    $announcements = Sina_Booking_Doctor::get_announcements();
    if (!empty($announcements)): ?>
    <div class="sina-announcements">
        <?php foreach ($announcements as $ann): ?>
        <div class="sina-announcement <?php echo esc_attr($ann->type); ?>">
            <strong><?php echo esc_html($ann->title); ?>:</strong> <?php echo esc_html($ann->message); ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="sina-section">
        <h2 class="sina-section-title">👨‍⚕️ انتخاب پزشک</h2>
        <p style="color: var(--sina-text-muted); font-size:14px;">پزشک مورد نظر خود را انتخاب کنید تا نوبت‌های خالی را ببینید</p>
    </div>

    <div class="sina-doctors-grid cols-<?php echo esc_attr($columns); ?>">
        <?php foreach ($doctors as $doctor): ?>
        <div class="sina-doctor-card" data-id="<?php echo esc_attr($doctor->id); ?>">
            <?php if ($doctor->avatar_url): ?>
                <img src="<?php echo esc_url($doctor->avatar_url); ?>" alt="<?php echo esc_attr($doctor->name); ?>" class="sina-doctor-avatar">
            <?php else: ?>
                <div class="sina-doctor-avatar-placeholder"><?php echo esc_html(mb_substr($doctor->name, 0, 1)); ?></div>
            <?php endif; ?>
            
            <h3 class="sina-doctor-name"><?php echo esc_html($doctor->name); ?></h3>
            
            <?php if ($show_specialty): ?>
            <div style="text-align:center; margin-bottom:12px;">
                <span class="sina-doctor-specialty"><?php echo esc_html($doctor->specialty); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($doctor->bio): ?>
            <p class="sina-doctor-bio"><?php echo esc_html(mb_substr($doctor->bio, 0, 100)); ?>...</p>
            <?php endif; ?>
            
            <div class="sina-doctor-fee">
                💰 ویزیت: <strong><?php echo number_format($doctor->consultation_fee); ?> تومان</strong>
            </div>
            
            <?php if ($doctor->announcement): ?>
            <div class="sina-alert sina-alert-warning" style="margin-top:12px; font-size:12px; padding:8px;">
                📢 <?php echo esc_html($doctor->announcement); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($doctors)): ?>
    <div class="sina-alert sina-alert-info">در حال حاضر پزشکی ثبت نشده است</div>
    <?php endif; ?>
</div>

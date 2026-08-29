<?php if (!defined('ABSPATH')) exit;
global $wpdb;
$table_app = $wpdb->prefix . 'sina_appointments';
$table_docs = $wpdb->prefix . 'sina_doctors';
$table_slots = $wpdb->prefix . 'sina_slots';

$total_doctors = $wpdb->get_var("SELECT COUNT(*) FROM $table_docs WHERE is_active=1");
$today = current_time('Y-m-d');
$today_appointments = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_app WHERE slot_date = %s", $today));
$pending_appointments = $wpdb->get_var("SELECT COUNT(*) FROM $table_app WHERE status='pending'");
$total_appointments = $wpdb->get_var("SELECT COUNT(*) FROM $table_app");
$today_free_slots = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_slots WHERE slot_date = %s AND is_booked=0 AND is_blocked=0", $today));
$revenue_today = $wpdb->get_var($wpdb->prepare("SELECT SUM(paid_amount) FROM $table_app WHERE slot_date = %s", $today)) ?: 0;

$recent_appointments = $wpdb->get_results("SELECT * FROM $table_app ORDER BY created_at DESC LIMIT 10");
?>
<div class="wrap sina-admin-wrap">
    <div class="sina-admin-header">
        <h1>📊 داشبورد نوبت دهی سینا</h1>
        <div>
            <button class="sina-btn sina-btn-secondary" style="background:white;" id="sinaGenerateSlots">⚙️ تولید اسلات 30 روز آینده</button>
        </div>
    </div>

    <div class="sina-stats-grid">
        <div class="sina-stat-card">
            <div>
                <div class="sina-stat-number"><?php echo esc_html($total_doctors); ?></div>
                <div class="sina-stat-label">پزشک فعال</div>
            </div>
            <div class="sina-stat-icon" style="background:#dbeafe; color:#1e40af;">👨‍⚕️</div>
        </div>
        <div class="sina-stat-card">
            <div>
                <div class="sina-stat-number"><?php echo esc_html($today_appointments); ?></div>
                <div class="sina-stat-label">نوبت امروز</div>
            </div>
            <div class="sina-stat-icon" style="background:#dcfce7; color:#15803d;">📅</div>
        </div>
        <div class="sina-stat-card">
            <div>
                <div class="sina-stat-number"><?php echo esc_html($pending_appointments); ?></div>
                <div class="sina-stat-label">در انتظار تایید</div>
            </div>
            <div class="sina-stat-icon" style="background:#fef3c7; color:#92400e;">⏳</div>
        </div>
        <div class="sina-stat-card">
            <div>
                <div class="sina-stat-number"><?php echo esc_html($today_free_slots); ?></div>
                <div class="sina-stat-label">ظرفیت خالی امروز</div>
            </div>
            <div class="sina-stat-icon" style="background:#f0f9ff; color:#0284c7;">🕐</div>
        </div>
        <div class="sina-stat-card">
            <div>
                <div class="sina-stat-number"><?php echo number_format($revenue_today); ?></div>
                <div class="sina-stat-label">درآمد امروز (تومان)</div>
            </div>
            <div class="sina-stat-icon" style="background:#f0fdf4; color:#15803d;">💰</div>
        </div>
        <div class="sina-stat-card">
            <div>
                <div class="sina-stat-number"><?php echo esc_html($total_appointments); ?></div>
                <div class="sina-stat-label">کل نوبت‌ها</div>
            </div>
            <div class="sina-stat-icon" style="background:#f5f3ff; color:#6d28d9;">📊</div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 2fr 1fr; gap:16px;">
        <div class="sina-admin-card">
            <h3>🕒 آخرین نوبت‌های ثبت شده</h3>
            <table class="sina-admin-table">
                <thead>
                    <tr><th>کد رهگیری</th><th>بیمار</th><th>پزشک</th><th>تاریخ</th><th>ساعت</th><th>وضعیت</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_appointments as $app):
                        $doc = $wpdb->get_row($wpdb->prepare("SELECT name FROM $table_docs WHERE id=%d", $app->doctor_id));
                    ?>
                    <tr>
                        <td><code><?php echo esc_html($app->tracking_code); ?></code></td>
                        <td><?php echo esc_html($app->patient_name); ?><br><small><?php echo esc_html($app->patient_phone); ?></small></td>
                        <td><?php echo esc_html($doc ? $doc->name : '-'); ?></td>
                        <td><?php echo esc_html($app->slot_date); ?></td>
                        <td><?php echo esc_html(substr($app->slot_time,0,5)); ?></td>
                        <td><span class="sina-badge sina-badge-info"><?php echo esc_html($app->status); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="text-align:center; margin-top:12px;"><a href="<?php echo admin_url('admin.php?page=sina-booking-appointments'); ?>" class="sina-btn sina-btn-primary">مشاهده همه نوبت‌ها</a></p>
        </div>

        <div>
            <div class="sina-admin-card">
                <h3>🚀 دسترسی سریع</h3>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <a href="<?php echo admin_url('admin.php?page=sina-booking-doctors'); ?>" class="sina-btn sina-btn-secondary" style="justify-content:flex-start; padding:12px;">👨‍⚕️ مدیریت پزشکان</a>
                    <a href="<?php echo admin_url('admin.php?page=sina-booking-schedules'); ?>" class="sina-btn sina-btn-secondary" style="justify-content:flex-start; padding:12px;">📅 برنامه کاری</a>
                    <a href="<?php echo admin_url('admin.php?page=sina-booking-appointments'); ?>" class="sina-btn sina-btn-secondary" style="justify-content:flex-start; padding:12px;">📋 لیست نوبت‌ها</a>
                    <a href="<?php echo admin_url('admin.php?page=sina-booking-services'); ?>" class="sina-btn sina-btn-secondary" style="justify-content:flex-start; padding:12px;">💼 خدمات و تعرفه</a>
                    <a href="<?php echo home_url('/sina-booking/'); ?>" target="_blank" class="sina-btn sina-btn-primary" style="justify-content:flex-start; padding:12px;">🌐 مشاهده سایت نوبت دهی</a>
                </div>
            </div>

            <div class="sina-admin-card">
                <h3>📝 راهنمای شورت‌کدها</h3>
                <div style="font-size:12px; line-height:1.8; background:#f8fafc; padding:12px; border-radius:8px; font-family:monospace;">
                    [sina_booking]<br>
                    [sina_doctors_list]<br>
                    [sina_patient_panel]<br>
                    [sina_secretary_panel]<br>
                    [sina_tracking]
                </div>
                <p style="font-size:12px; color:#64748b; margin-top:8px;">این شورت‌کدها را در برگه‌ها قرار دهید. المنتور: دسته "نوبت دهی سینا"</p>
            </div>

            <div class="sina-admin-card">
                <h3>⚙️ تولید خودکار اسلات</h3>
                <div style="display:flex; gap:8px; margin-top:8px;">
                    <select id="generate_doctor_id" class="sina-select" style="flex:1;">
                        <option value="">همه پزشکان</option>
                        <?php
                        $docs = $wpdb->get_results("SELECT id, name FROM $table_docs WHERE is_active=1");
                        foreach ($docs as $d) echo '<option value="'.$d->id.'">'.$d->name.'</option>';
                        ?>
                    </select>
                    <input type="number" id="generate_days" value="30" class="sina-input" style="width:80px;" placeholder="روز">
                </div>
                <button id="sinaGenerateSlots2" class="sina-btn sina-btn-primary" style="width:100%; margin-top:8px; justify-content:center;">تولید اسلات‌ها</button>
                <script>
                jQuery(document).ready(function($){
                    $('#sinaGenerateSlots, #sinaGenerateSlots2').on('click', function(){
                        const doctor_id = $('#generate_doctor_id').val();
                        const days = $('#generate_days').val() || 30;
                        const btn = $(this);
                        btn.prop('disabled', true).text('در حال تولید...');
                        $.post(ajaxurl, {
                            action: 'sina_admin_generate_slots',
                            doctor_id: doctor_id,
                            days: days,
                            nonce: '<?php echo wp_create_nonce('sina_booking_admin_nonce'); ?>'
                        }, function(res){
                            btn.prop('disabled', false).text('تولید اسلات‌ها');
                            alert(res.data.message);
                        });
                    });
                });
                </script>
            </div>
        </div>
    </div>
</div>

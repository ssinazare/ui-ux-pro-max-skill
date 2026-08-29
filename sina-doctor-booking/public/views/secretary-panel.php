<?php if (!defined('ABSPATH')) exit;
global $wpdb;

$doctor_id_filter = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : $doctor_id;
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$date_filter = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : current_time('Y-m-d');
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

$doctors = Sina_Booking_Doctor::get_all(false);
$services_table = $wpdb->prefix . 'sina_services';

$args = [
    'limit' => 100,
    'orderby' => 'slot_date',
    'order' => 'ASC',
];
if ($doctor_id_filter) $args['doctor_id'] = $doctor_id_filter;
if ($status_filter) $args['status'] = $status_filter;
if ($date_filter) $args['date_from'] = $date_filter;
if ($search) $args['search'] = $search;

// If secretary has specific doctor, override
$my_doctor_id = Sina_Booking_Roles::get_user_doctor_id();
if ($my_doctor_id && !current_user_can('sina_manage_all')) {
    $args['doctor_id'] = $my_doctor_id;
    $doctor_id_filter = $my_doctor_id;
}

$appointments = Sina_Booking_Appointment::get_appointments($args);
$settings = get_option('sina_booking_settings', []);

?>
<div class="sina-booking-wrapper">
    <div class="sina-section">
        <div class="sina-secretary-header">
            <h2 class="sina-section-title" style="margin:0;">📋 پنل منشی - مدیریت نوبت‌ها</h2>
            <div style="display:flex; gap:8px;">
                <button onclick="window.print()" class="sina-btn sina-btn-secondary">🖨️ چاپ لیست امروز</button>
                <a href="<?php echo home_url('/sina-booking/'); ?>" class="sina-btn sina-btn-primary">➕ نوبت جدید</a>
            </div>
        </div>

        <form id="sinaSecretaryFilter" class="sina-filters" method="get">
            <?php if (current_user_can('sina_manage_all') || !$my_doctor_id): ?>
            <select name="doctor_id" class="sina-select" style="min-width:180px;">
                <option value="">همه پزشکان</option>
                <?php foreach ($doctors as $doc): ?>
                <option value="<?php echo esc_attr($doc->id); ?>" <?php selected($doctor_id_filter, $doc->id); ?>><?php echo esc_html($doc->name); ?></option>
                <?php endforeach; ?>
            </select>
            <?php else: 
                $doc = Sina_Booking_Doctor::get_by_id($my_doctor_id);
            ?>
            <div style="background:#f0f9ff; padding:8px 16px; border-radius:8px; border:1px solid #0ea5e9;">
                👨‍⚕️ <?php echo esc_html($doc->name); ?>
            </div>
            <?php endif; ?>

            <input type="date" name="date" value="<?php echo esc_attr($date_filter); ?>" class="sina-input" style="min-width:160px;">

            <select name="status" class="sina-select" style="min-width:140px;">
                <option value="">همه وضعیت‌ها</option>
                <option value="pending" <?php selected($status_filter, 'pending'); ?>>در انتظار</option>
                <option value="confirmed" <?php selected($status_filter, 'confirmed'); ?>>تایید شده</option>
                <option value="visited" <?php selected($status_filter, 'visited'); ?>>ویزیت شده</option>
                <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>>لغو شده</option>
            </select>

            <input type="text" name="search" id="sinaSearchInput" value="<?php echo esc_attr($search); ?>" placeholder="جستجو نام، موبایل، کد رهگیری..." class="sina-input" style="min-width:200px;">

            <button type="submit" class="sina-btn sina-btn-primary">🔍 فیلتر</button>
            <a href="?" class="sina-btn sina-btn-secondary">♻️ پاک کردن</a>
        </form>

        <div style="margin-top:16px; display:flex; gap:12px; font-size:13px;">
            <span>📊 تعداد: <strong><?php echo count($appointments); ?></strong> نوبت</span>
            <span>📅 تاریخ: <strong><?php echo esc_html($date_filter ? Sina_Booking_Schedule::format_jalali($date_filter) : 'همه'); ?></strong></span>
        </div>
    </div>

    <div class="sina-appointments-list" id="sinaSecretaryList">
        <?php if (empty($appointments)): ?>
            <div class="sina-alert sina-alert-info">نوبتی با این فیلتر یافت نشد</div>
        <?php else: ?>
            <?php foreach ($appointments as $app):
                $doctor = Sina_Booking_Doctor::get_by_id($app->doctor_id);
                $services = json_decode($app->services, true) ?: [];
                $all_services = $wpdb->get_results($wpdb->prepare("SELECT * FROM $services_table WHERE doctor_id = %d AND is_active = 1", $app->doctor_id));
            ?>
            <div class="sina-appointment-card" data-id="<?php echo esc_attr($app->id); ?>" style="flex-direction:column; align-items:stretch;">
                <div style="display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap;">
                    <div>
                        <h4 style="margin:0 0 8px; font-size:16px;">
                            <?php echo esc_html($app->patient_name); ?> 
                            <small style="color:var(--sina-text-muted);">(<?php echo esc_html($app->patient_phone); ?>)</small>
                        </h4>
                        <div class="sina-appointment-meta">
                            <span>👨‍⚕️ <?php echo esc_html($doctor ? $doctor->name : ''); ?></span>
                            <span>📅 <?php echo esc_html(Sina_Booking_Schedule::format_jalali($app->slot_date)); ?> - ⏰ <?php echo esc_html(substr($app->slot_time,0,5)); ?></span>
                            <span>🔖 <?php echo esc_html($app->tracking_code); ?></span>
                            <span>🆔 <?php echo esc_html($app->patient_national_id); ?></span>
                        </div>
                        <?php if ($app->notes): ?>
                        <div style="margin-top:8px; font-size:12px; color:#475569;">📝 <?php echo esc_html($app->notes); ?></div>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:8px; min-width:200px;">
                        <div style="display:flex; gap:8px; align-items:center;">
                            <label style="font-size:12px; min-width:60px;">وضعیت:</label>
                            <select class="sina-select sina-status-select" data-id="<?php echo esc_attr($app->id); ?>" style="font-size:13px; padding:6px;">
                                <option value="pending" <?php selected($app->status, 'pending'); ?>>در انتظار</option>
                                <option value="confirmed" <?php selected($app->status, 'confirmed'); ?>>تایید شده</option>
                                <option value="visited" <?php selected($app->status, 'visited'); ?>>ویزیت شده</option>
                                <option value="cancelled" <?php selected($app->status, 'cancelled'); ?>>لغو شده</option>
                                <option value="no_show" <?php selected($app->status, 'no_show'); ?>>عدم مراجعه</option>
                            </select>
                        </div>
                        <div style="display:flex; gap:8px; align-items:center;">
                            <label style="font-size:12px; min-width:60px;">پرداخت:</label>
                            <select class="sina-select sina-payment-select" data-id="<?php echo esc_attr($app->id); ?>" style="font-size:13px; padding:6px;">
                                <option value="unpaid" <?php selected($app->payment_status, 'unpaid'); ?>>پرداخت نشده</option>
                                <option value="paid" <?php selected($app->payment_status, 'paid'); ?>>پرداخت شده</option>
                                <option value="partial" <?php selected($app->payment_status, 'partial'); ?>>جزئی</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Services & Fees -->
                <div style="background:#f8fafc; padding:16px; border-radius:10px; margin-top:12px; border:1px solid #e2e8f0;">
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px;">
                        <div>
                            <label style="font-size:12px; font-weight:600;">خدمات انتخابی:</label>
                            <div class="sina-services-checks" data-id="<?php echo esc_attr($app->id); ?>" style="margin-top:6px; max-height:120px; overflow-y:auto; background:white; padding:8px; border-radius:8px; border:1px solid #e2e8f0;">
                                <?php foreach ($all_services as $srv): 
                                    $checked = false;
                                    foreach ($services as $s) {
                                        if (($s['id'] ?? 0) == $srv->id) { $checked = true; break; }
                                    }
                                ?>
                                <label style="display:flex; align-items:center; gap:6px; font-size:13px; padding:4px 0; cursor:pointer;">
                                    <input type="checkbox" value="<?php echo esc_attr($srv->id); ?>" <?php checked($checked); ?> class="sina-service-check" data-price="<?php echo esc_attr($srv->price); ?>">
                                    <?php echo esc_html($srv->title); ?> - <?php echo number_format($srv->price); ?> تومان
                                </label>
                                <?php endforeach; ?>
                                <?php if (empty($all_services)): ?>
                                <small style="color:#94a3b8;">خدمتی ثبت نشده</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px;">
                                <div>
                                    <label style="font-size:12px;">مبلغ کل (تومان):</label>
                                    <input type="number" class="sina-input sina-total-fee" data-id="<?php echo esc_attr($app->id); ?>" value="<?php echo esc_attr($app->total_fee); ?>" style="padding:8px; font-size:13px;">
                                </div>
                                <div>
                                    <label style="font-size:12px;">مبلغ پرداختی:</label>
                                    <input type="number" class="sina-input sina-paid-amount" data-id="<?php echo esc_attr($app->id); ?>" value="<?php echo esc_attr($app->paid_amount); ?>" style="padding:8px; font-size:13px;">
                                </div>
                            </div>
                            <div style="margin-top:8px;">
                                <label style="font-size:12px;">تاریخ ویزیت واقعی:</label>
                                <input type="datetime-local" class="sina-input sina-visit-date" data-id="<?php echo esc_attr($app->id); ?>" value="<?php echo $app->visit_date ? esc_attr(date('Y-m-d\TH:i', strtotime($app->visit_date))) : ''; ?>" style="padding:8px; font-size:13px;">
                            </div>
                        </div>
                        <div>
                            <label style="font-size:12px;">یادداشت منشی:</label>
                            <textarea class="sina-textarea sina-secretary-notes" data-id="<?php echo esc_attr($app->id); ?>" rows="3" style="font-size:13px; padding:8px;" placeholder="یادداشت..."><?php echo esc_textarea($app->secretary_notes); ?></textarea>
                            <div style="display:flex; gap:6px; margin-top:8px;">
                                <button class="sina-btn sina-btn-primary sina-save-btn" data-id="<?php echo esc_attr($app->id); ?>" style="font-size:12px; padding:6px 12px; flex:1;">💾 ذخیره</button>
                                <button class="sina-btn sina-btn-success sina-calc-btn" data-id="<?php echo esc_attr($app->id); ?>" style="font-size:12px; padding:6px 12px;">🧮 محاسبه</button>
                                <a href="<?php echo home_url('/sina-print/'.$app->tracking_code.'/'); ?>" target="_blank" class="sina-btn sina-btn-secondary" style="font-size:12px; padding:6px 12px;">🖨️</a>
                                <button class="sina-btn sina-btn-danger sina-delete-btn" data-id="<?php echo esc_attr($app->id); ?>" style="font-size:12px; padding:6px 12px;">🗑️</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($){
    // Auto calculate when service checked
    $(document).on('change', '.sina-service-check', function(){
        const card = $(this).closest('.sina-appointment-card');
        const id = card.data('id');
        let base = <?php echo $doctors[0]->consultation_fee ?? 0; ?>;
        // We'll recalculate via ajax for accuracy
        const checked = [];
        card.find('.sina-service-check:checked').each(function(){
            checked.push($(this).val());
        });
        $.post(ajaxurl || sinaBooking.ajax_url, {
            action: 'sina_admin_calculate',
            id: id,
            services: JSON.stringify(checked),
            nonce: '<?php echo wp_create_nonce('sina_booking_admin_nonce'); ?>'
        }, function(res){
            if(res.success){
                card.find('.sina-total-fee').val(res.data.total);
            }
        });
    });

    $(document).on('click', '.sina-calc-btn', function(){
        const id = $(this).data('id');
        const card = $('.sina-appointment-card[data-id="'+id+'"]');
        const checked = [];
        card.find('.sina-service-check:checked').each(function(){ checked.push($(this).val()); });
        const btn = $(this);
        btn.text('...').prop('disabled', true);
        $.post(ajaxurl || sinaBooking.ajax_url, {
            action: 'sina_admin_calculate',
            id: id,
            services: JSON.stringify(checked),
            nonce: '<?php echo wp_create_nonce('sina_booking_admin_nonce'); ?>'
        }, function(res){
            btn.text('🧮 محاسبه').prop('disabled', false);
            if(res.success){
                card.find('.sina-total-fee').val(res.data.total);
                alert('مبلغ محاسبه شد: ' + res.data.formatted);
            }
        });
    });

    $(document).on('click', '.sina-save-btn', function(){
        const id = $(this).data('id');
        const card = $('.sina-appointment-card[data-id="'+id+'"]');
        const status = card.find('.sina-status-select').val();
        const payment_status = card.find('.sina-payment-select').val();
        const total_fee = card.find('.sina-total-fee').val();
        const paid_amount = card.find('.sina-paid-amount').val();
        const secretary_notes = card.find('.sina-secretary-notes').val();
        const visit_date = card.find('.sina-visit-date').val();
        const services = [];
        card.find('.sina-service-check:checked').each(function(){ services.push($(this).val()); });

        const btn = $(this);
        btn.text('...').prop('disabled', true);
        $.post(ajaxurl || sinaBooking.ajax_url, {
            action: 'sina_admin_update_status',
            id: id,
            status: status,
            payment_status: payment_status,
            total_fee: total_fee,
            paid_amount: paid_amount,
            secretary_notes: secretary_notes,
            visit_date: visit_date,
            services: JSON.stringify(services),
            nonce: '<?php echo wp_create_nonce('sina_booking_admin_nonce'); ?>'
        }, function(res){
            btn.text('💾 ذخیره').prop('disabled', false);
            if(res.success){
                btn.text('✅ ذخیره شد');
                setTimeout(()=>btn.text('💾 ذخیره'), 2000);
            } else {
                alert(res.data.message);
            }
        });
    });

    $(document).on('click', '.sina-delete-btn', function(){
        if(!confirm('آیا از حذف این نوبت اطمینان دارید؟')) return;
        const id = $(this).data('id');
        const card = $('.sina-appointment-card[data-id="'+id+'"]');
        $.post(ajaxurl || sinaBooking.ajax_url, {
            action: 'sina_admin_delete_appointment',
            id: id,
            nonce: '<?php echo wp_create_nonce('sina_booking_admin_nonce'); ?>'
        }, function(res){
            if(res.success){
                card.fadeOut(300, function(){ $(this).remove(); });
            } else {
                alert(res.data.message);
            }
        });
    });

    // Change status quickly
    $(document).on('change', '.sina-status-select', function(){
        const id = $(this).data('id');
        const status = $(this).val();
        // Auto set visit date if visited
        if(status === 'visited'){
            const card = $('.sina-appointment-card[data-id="'+id+'"]');
            if(!card.find('.sina-visit-date').val()){
                const now = new Date();
                const local = new Date(now.getTime() - now.getTimezoneOffset()*60000).toISOString().slice(0,16);
                card.find('.sina-visit-date').val(local);
            }
        }
    });
});
</script>

<style>
@media print {
    .sina-filters, .sina-secretary-header button, .sina-save-btn, .sina-delete-btn, .sina-calc-btn { display:none !important; }
    .sina-booking-wrapper { padding:0; }
    .sina-appointment-card { break-inside: avoid; border:1px solid #000 !important; }
}
</style>

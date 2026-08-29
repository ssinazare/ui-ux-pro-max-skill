<?php if (!defined('ABSPATH')) exit;
global $wpdb;
$table_a = $wpdb->prefix . 'sina_announcements';
$table_d = $wpdb->prefix . 'sina_doctors';
$doctors = $wpdb->get_results("SELECT id, name FROM $table_d WHERE is_active=1");
$anns = $wpdb->get_results("SELECT a.*, d.name as doctor_name FROM $table_a a LEFT JOIN $table_d d ON a.doctor_id=d.id ORDER BY a.created_at DESC");
?>
<div class="wrap sina-admin-wrap">
    <div class="sina-admin-header">
        <h1>📢 اطلاعیه‌ها</h1>
        <button class="sina-btn" style="background:white; color:#0ea5e9;" data-modal="#annModal">➕ اطلاعیه جدید</button>
    </div>

    <div class="sina-admin-card">
        <p style="font-size:13px; color:#64748b;">اطلاعیه‌ها در صفحه نوبت‌دهی نمایش داده می‌شوند. می‌توانید برای پزشک خاص یا همه پزشکان اطلاعیه ثبت کنید. مثلا: "دکتر فلان تاریخ حضور ندارند" یا "لطفا 15 دقیقه زودتر حضور داشته باشید"</p>
        <table class="sina-admin-table">
            <thead><tr><th>ID</th><th>پزشک</th><th>عنوان</th><th>پیام</th><th>نوع</th><th>بازه زمانی</th><th>وضعیت</th><th>عملیات</th></tr></thead>
            <tbody>
                <?php foreach ($anns as $a): ?>
                <tr>
                    <td><?php echo esc_html($a->id); ?></td>
                    <td><?php echo $a->doctor_id ? esc_html($a->doctor_name) : '<span class="sina-badge sina-badge-info">همه</span>'; ?></td>
                    <td><strong><?php echo esc_html($a->title); ?></strong></td>
                    <td><?php echo esc_html(mb_substr($a->message,0,80)); ?>...</td>
                    <td><span class="sina-badge sina-badge-<?php echo $a->type==='danger' ? 'danger' : ($a->type==='warning' ? 'warning' : 'info'); ?>"><?php echo esc_html($a->type); ?></span></td>
                    <td><?php echo esc_html($a->start_date ?: '-'); ?> تا <?php echo esc_html($a->end_date ?: '-'); ?></td>
                    <td><?php echo $a->is_active ? '✅ فعال' : '❌ غیرفعال'; ?></td>
                    <td><button class="sina-btn sina-btn-danger" onclick="if(confirm('حذف شود؟')){ jQuery.post(ajaxurl,{action:'sina_admin_delete_announcement',id:<?php echo $a->id; ?>,nonce:'<?php echo wp_create_nonce('sina_booking_admin_nonce'); ?>'},function(){location.reload();}); }">🗑️</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="sina-modal" id="annModal">
    <div class="sina-modal-content">
        <h3>📢 اطلاعیه جدید</h3>
        <form id="sinaAnnouncementForm">
            <div class="sina-form-row">
                <div>
                    <label>پزشک (خالی = همه)</label>
                    <select name="doctor_id" class="sina-select">
                        <option value="">همه پزشکان (اطلاعیه عمومی)</option>
                        <?php foreach ($doctors as $d): ?>
                        <option value="<?php echo esc_attr($d->id); ?>"><?php echo esc_html($d->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>نوع اطلاعیه</label>
                    <select name="type" class="sina-select">
                        <option value="info">آبی - اطلاع</option>
                        <option value="warning">زرد - هشدار</option>
                        <option value="danger">قرمز - مهم</option>
                        <option value="success">سبز - موفقیت</option>
                    </select>
                </div>
            </div>
            <div style="margin-top:12px;">
                <label>عنوان *</label>
                <input type="text" name="title" class="sina-input" required placeholder="مثلا: اطلاعیه تعطیلی">
            </div>
            <div style="margin-top:12px;">
                <label>متن پیام *</label>
                <textarea name="message" class="sina-textarea" rows="3" required placeholder="مثلا: دکتر احمدی در تاریخ 1403/05/10 حضور ندارند"></textarea>
            </div>
            <div class="sina-form-row" style="margin-top:12px;">
                <div>
                    <label>از تاریخ</label>
                    <input type="date" name="start_date" class="sina-input">
                </div>
                <div>
                    <label>تا تاریخ</label>
                    <input type="date" name="end_date" class="sina-input">
                </div>
            </div>
            <div style="margin-top:12px; display:flex; gap:8px; justify-content:flex-end;">
                <button type="button" class="sina-btn sina-btn-secondary sina-modal-close">انصراف</button>
                <button type="submit" class="sina-btn sina-btn-primary">ذخیره اطلاعیه</button>
            </div>
        </form>
    </div>
</div>

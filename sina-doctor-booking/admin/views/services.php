<?php if (!defined('ABSPATH')) exit;
global $wpdb;
$table_s = $wpdb->prefix . 'sina_services';
$table_d = $wpdb->prefix . 'sina_doctors';

$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$doctors = $wpdb->get_results("SELECT id, name FROM $table_d WHERE is_active=1");
$where = $doctor_id ? $wpdb->prepare("WHERE doctor_id=%d", $doctor_id) : "";
$services = $wpdb->get_results("SELECT s.*, d.name as doctor_name FROM $table_s s LEFT JOIN $table_d d ON s.doctor_id=d.id $where ORDER BY doctor_id, title");
?>
<div class="wrap sina-admin-wrap">
    <div class="sina-admin-header">
        <h1>💼 خدمات و تعرفه‌ها</h1>
        <button class="sina-btn" style="background:white; color:#0ea5e9;" data-modal="#serviceModal">➕ افزودن خدمت</button>
    </div>

    <div class="sina-admin-card">
        <form method="get" style="display:flex; gap:12px;">
            <input type="hidden" name="page" value="sina-booking-services">
            <select name="doctor_id" class="sina-select" style="min-width:200px;">
                <option value="">همه پزشکان</option>
                <?php foreach ($doctors as $d): ?>
                <option value="<?php echo esc_attr($d->id); ?>" <?php selected($doctor_id, $d->id); ?>><?php echo esc_html($d->name); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="sina-btn sina-btn-primary">فیلتر</button>
        </form>
    </div>

    <div class="sina-admin-card">
        <table class="sina-admin-table">
            <thead><tr><th>ID</th><th>پزشک</th><th>عنوان خدمت</th><th>قیمت (تومان)</th><th>مدت (دقیقه)</th><th>وضعیت</th><th>عملیات</th></tr></thead>
            <tbody>
                <?php foreach ($services as $s): ?>
                <tr>
                    <td><?php echo esc_html($s->id); ?></td>
                    <td><?php echo esc_html($s->doctor_name); ?></td>
                    <td><strong><?php echo esc_html($s->title); ?></strong><br><small><?php echo esc_html($s->description); ?></small></td>
                    <td><?php echo number_format($s->price); ?></td>
                    <td><?php echo esc_html($s->duration); ?></td>
                    <td><span class="sina-badge <?php echo $s->is_active ? 'sina-badge-success' : 'sina-badge-danger'; ?>"><?php echo $s->is_active ? 'فعال' : 'غیرفعال'; ?></span></td>
                    <td>
                        <button class="sina-btn sina-btn-danger" onclick="if(confirm('حذف شود؟')){ jQuery.post(ajaxurl,{action:'sina_admin_delete_service',id:<?php echo $s->id; ?>,nonce:'<?php echo wp_create_nonce('sina_booking_admin_nonce'); ?>'},function(){location.reload();}); }">🗑️ حذف</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="sina-modal" id="serviceModal">
    <div class="sina-modal-content">
        <h3>💼 افزودن خدمت جدید</h3>
        <form id="sinaServiceForm">
            <div>
                <label>پزشک *</label>
                <select name="doctor_id" class="sina-select" required>
                    <?php foreach ($doctors as $d): ?>
                    <option value="<?php echo esc_attr($d->id); ?>"><?php echo esc_html($d->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-top:12px;">
                <label>عنوان خدمت *</label>
                <input type="text" name="title" class="sina-input" required placeholder="مثلا: نوار قلب، سونوگرافی">
            </div>
            <div style="margin-top:12px;">
                <label>توضیحات</label>
                <textarea name="description" class="sina-textarea" rows="2"></textarea>
            </div>
            <div class="sina-form-row" style="margin-top:12px;">
                <div>
                    <label>قیمت (تومان) *</label>
                    <input type="number" name="price" class="sina-input" required placeholder="150000">
                </div>
                <div>
                    <label>مدت زمان (دقیقه)</label>
                    <input type="number" name="duration" class="sina-input" value="10">
                </div>
            </div>
            <div style="margin-top:12px; display:flex; gap:8px; justify-content:flex-end;">
                <button type="button" class="sina-btn sina-btn-secondary sina-modal-close">انصراف</button>
                <button type="submit" class="sina-btn sina-btn-primary">ذخیره خدمت</button>
            </div>
        </form>
    </div>
</div>

<?php if (!defined('ABSPATH')) exit;
global $wpdb;
$table_s = $wpdb->prefix . 'sina_schedules';
$table_d = $wpdb->prefix . 'sina_doctors';

$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$doctors = $wpdb->get_results("SELECT id, name FROM $table_d WHERE is_active=1 ORDER BY name");
$where = $doctor_id ? $wpdb->prepare("WHERE doctor_id=%d", $doctor_id) : "";
$schedules = $wpdb->get_results("SELECT s.*, d.name as doctor_name FROM $table_s s LEFT JOIN $table_d d ON s.doctor_id=d.id $where ORDER BY doctor_id, day_of_week, start_time");

$day_names = ['شنبه','یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه'];
?>
<div class="wrap sina-admin-wrap">
    <div class="sina-admin-header">
        <h1>📅 برنامه کاری پزشکان</h1>
        <button class="sina-btn" style="background:white; color:#0ea5e9;" data-modal="#scheduleModal">➕ افزودن برنامه</button>
    </div>

    <div class="sina-admin-card">
        <form method="get" style="display:flex; gap:12px; align-items:center;">
            <input type="hidden" name="page" value="sina-booking-schedules">
            <select name="doctor_id" class="sina-select" style="min-width:200px;">
                <option value="">همه پزشکان</option>
                <?php foreach ($doctors as $d): ?>
                <option value="<?php echo esc_attr($d->id); ?>" <?php selected($doctor_id, $d->id); ?>><?php echo esc_html($d->name); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="sina-btn sina-btn-primary">فیلتر</button>
            <a href="<?php echo admin_url('admin.php?page=sina-booking-schedules'); ?>" class="sina-btn sina-btn-secondary">پاک کردن</a>
        </form>
    </div>

    <div class="sina-admin-card">
        <table class="sina-admin-table">
            <thead>
                <tr><th>ID</th><th>پزشک</th><th>روز هفته</th><th>تاریخ خاص</th><th>شروع</th><th>پایان</th><th>مدت اسلات</th><th>وضعیت</th><th>عملیات</th></tr>
            </thead>
            <tbody>
                <?php foreach ($schedules as $s): ?>
                <tr>
                    <td><?php echo esc_html($s->id); ?></td>
                    <td><?php echo esc_html($s->doctor_name); ?></td>
                    <td><?php echo esc_html($day_names[$s->day_of_week] ?? $s->day_of_week); ?></td>
                    <td><?php echo esc_html($s->date_specific ?: '-'); ?></td>
                    <td><?php echo esc_html(substr($s->start_time,0,5)); ?></td>
                    <td><?php echo esc_html(substr($s->end_time,0,5)); ?></td>
                    <td><?php echo esc_html($s->slot_duration); ?> دقیقه</td>
                    <td><span class="sina-badge <?php echo $s->is_active ? 'sina-badge-success' : 'sina-badge-danger'; ?>"><?php echo $s->is_active ? 'فعال' : 'غیرفعال'; ?></span></td>
                    <td>
                        <button class="sina-btn sina-btn-danger sina-delete-schedule" data-id="<?php echo esc_attr($s->id); ?>">🗑️</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top:20px; background:#f8fafc; padding:16px; border-radius:8px; border:1px solid #e2e8f0;">
            <h4>💡 راهنما:</h4>
            <ul style="font-size:13px; line-height:1.8; margin:0; padding-right:20px;">
                <li>برای برنامه هفتگی، روز هفته را انتخاب کنید و تاریخ خاص را خالی بگذارید</li>
                <li>برای بستن یک روز خاص (مثلا تعطیلی)، تاریخ خاص را پر کنید و وضعیت را غیرفعال بگذارید و در اطلاعیه دلیل را بنویسید</li>
                <li>بعد از تعریف برنامه، حتما دکمه "تولید اسلات‌ها" را بزنید تا نوبت‌های قابل رزرو ساخته شوند</li>
                <li>اسلات‌ها برای 30 روز آینده تولید می‌شوند. هر روز می‌توانید مجدد تولید کنید</li>
            </ul>
        </div>
    </div>
</div>

<div class="sina-modal" id="scheduleModal">
    <div class="sina-modal-content">
        <h3>📅 افزودن برنامه کاری</h3>
        <form id="sinaScheduleForm">
            <div class="sina-form-row">
                <div>
                    <label>پزشک *</label>
                    <select name="doctor_id" class="sina-select" required>
                        <?php foreach ($doctors as $d): ?>
                        <option value="<?php echo esc_attr($d->id); ?>" <?php selected($doctor_id, $d->id); ?>><?php echo esc_html($d->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>روز هفته</label>
                    <select name="day_of_week" class="sina-select">
                        <?php foreach ($day_names as $i=>$name): ?>
                        <option value="<?php echo $i; ?>"><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div>
                <label>تاریخ خاص (برای تعطیلی یا برنامه خاص - اختیاری)</label>
                <input type="date" name="date_specific" class="sina-input">
                <small style="color:#64748b;">اگر پر شود، فقط برای همان تاریخ اعمال می‌شود. برای تعطیلی، غیرفعال کنید</small>
            </div>
            <div class="sina-form-row">
                <div>
                    <label>ساعت شروع</label>
                    <input type="time" name="start_time" class="sina-input" value="09:00" required>
                </div>
                <div>
                    <label>ساعت پایان</label>
                    <input type="time" name="end_time" class="sina-input" value="13:00" required>
                </div>
            </div>
            <div class="sina-form-row">
                <div>
                    <label>مدت هر نوبت (دقیقه)</label>
                    <select name="slot_duration" class="sina-select">
                        <option value="10">10 دقیقه</option>
                        <option value="15" selected>15 دقیقه</option>
                        <option value="20">20 دقیقه</option>
                        <option value="30">30 دقیقه</option>
                    </select>
                </div>
                <div>
                    <label>وضعیت</label>
                    <select name="is_active" class="sina-select">
                        <option value="1">فعال</option>
                        <option value="0">غیرفعال / تعطیل</option>
                    </select>
                </div>
            </div>
            <div>
                <label>اطلاعیه (مثلا: دکتر فلان ساعت حضور ندارند)</label>
                <textarea name="announcement" class="sina-textarea" rows="2"></textarea>
            </div>
            <div style="display:flex; gap:8px; margin-top:16px; justify-content:flex-end;">
                <button type="button" class="sina-btn sina-btn-secondary sina-modal-close">انصراف</button>
                <button type="submit" class="sina-btn sina-btn-primary">ذخیره برنامه</button>
            </div>
        </form>
    </div>
</div>

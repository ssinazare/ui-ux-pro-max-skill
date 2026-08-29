<?php if (!defined('ABSPATH')) exit;
global $wpdb;
$table_app = $wpdb->prefix . 'sina_appointments';
$table_docs = $wpdb->prefix . 'sina_doctors';

$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$tracking = isset($_GET['tracking']) ? sanitize_text_field($_GET['tracking']) : '';

$doctors = $wpdb->get_results("SELECT id, name FROM $table_docs WHERE is_active=1");

$args = [
    'doctor_id' => $doctor_id ?: null,
    'status' => $status ?: null,
    'date_from' => $date_from ?: null,
    'date_to' => $date_to ?: null,
    'search' => $search ?: ($tracking ?: null),
    'limit' => 100,
    'orderby' => 'slot_date',
    'order' => 'DESC',
];
$appointments = Sina_Booking_Appointment::get_appointments($args);

?>
<div class="wrap sina-admin-wrap">
    <div class="sina-admin-header">
        <h1>📋 مدیریت نوبت‌ها</h1>
        <div style="display:flex; gap:8px;">
            <a href="<?php echo home_url('/sina-secretary-panel/'); ?>" target="_blank" class="sina-btn" style="background:white; color:#0ea5e9;">👩‍💼 پنل منشی</a>
            <button class="sina-btn" style="background:white; color:#0ea5e9;" onclick="window.print()">🖨️ چاپ لیست</button>
        </div>
    </div>

    <div class="sina-admin-card">
        <form method="get" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:12px;">
            <input type="hidden" name="page" value="sina-booking-appointments">
            <select name="doctor_id" class="sina-select">
                <option value="">همه پزشکان</option>
                <?php foreach ($doctors as $d): ?>
                <option value="<?php echo esc_attr($d->id); ?>" <?php selected($doctor_id, $d->id); ?>><?php echo esc_html($d->name); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="sina-select">
                <option value="">همه وضعیت‌ها</option>
                <option value="pending" <?php selected($status, 'pending'); ?>>در انتظار</option>
                <option value="confirmed" <?php selected($status, 'confirmed'); ?>>تایید شده</option>
                <option value="visited" <?php selected($status, 'visited'); ?>>ویزیت شده</option>
                <option value="cancelled" <?php selected($status, 'cancelled'); ?>>لغو شده</option>
                <option value="no_show" <?php selected($status, 'no_show'); ?>>عدم مراجعه</option>
            </select>
            <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" class="sina-input" placeholder="از تاریخ">
            <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" class="sina-input" placeholder="تا تاریخ">
            <input type="text" name="search" value="<?php echo esc_attr($search); ?>" class="sina-input" placeholder="جستجو نام، موبایل، کد...">
            <div style="display:flex; gap:8px;">
                <button type="submit" class="sina-btn sina-btn-primary">🔍 فیلتر</button>
                <a href="<?php echo admin_url('admin.php?page=sina-booking-appointments'); ?>" class="sina-btn sina-btn-secondary">♻️</a>
            </div>
        </form>
    </div>

    <div class="sina-admin-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h3 style="margin:0;">تعداد: <?php echo count($appointments); ?> نوبت</h3>
            <div style="font-size:12px; color:#64748b;">برای ویرایش جزئیات (هزینه، خدمات، تاریخ ویزیت) از پنل منشی استفاده کنید - قابلیت پرینت حرارتی دارد</div>
        </div>
        <div style="overflow-x:auto;">
        <table class="sina-admin-table">
            <thead>
                <tr>
                    <th>کد رهگیری</th><th>بیمار</th><th>پزشک</th><th>تاریخ و ساعت</th><th>مبلغ</th><th>وضعیت</th><th>پرداخت</th><th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $app):
                    $doc = $wpdb->get_row($wpdb->prepare("SELECT name FROM $table_docs WHERE id=%d", $app->doctor_id));
                ?>
                <tr>
                    <td><code style="background:#f1f5f9; padding:2px 6px; border-radius:4px;"><?php echo esc_html($app->tracking_code); ?></code></td>
                    <td>
                        <strong><?php echo esc_html($app->patient_name); ?></strong><br>
                        <small><?php echo esc_html($app->patient_phone); ?></small>
                        <?php if ($app->patient_national_id): ?><br><small>کد ملی: <?php echo esc_html($app->patient_national_id); ?></small><?php endif; ?>
                    </td>
                    <td><?php echo esc_html($doc ? $doc->name : '-'); ?></td>
                    <td>
                        <?php echo esc_html($app->slot_date); ?><br>
                        <strong><?php echo esc_html(substr($app->slot_time,0,5)); ?></strong><br>
                        <small><?php echo esc_html(Sina_Booking_Schedule::format_jalali($app->slot_date)); ?></small>
                    </td>
                    <td>
                        کل: <?php echo number_format($app->total_fee); ?><br>
                        پرداختی: <?php echo number_format($app->paid_amount); ?>
                    </td>
                    <td>
                        <span class="sina-badge 
                            <?php echo $app->status==='visited' ? 'sina-badge-success' : ($app->status==='cancelled' ? 'sina-badge-danger' : 'sina-badge-warning'); ?>">
                            <?php echo esc_html($app->status); ?>
                        </span>
                    </td>
                    <td><span class="sina-badge <?php echo $app->payment_status==='paid' ? 'sina-badge-success' : 'sina-badge-danger'; ?>"><?php echo esc_html($app->payment_status); ?></span></td>
                    <td>
                        <a href="<?php echo home_url('/sina-print/'.$app->tracking_code.'/'); ?>" target="_blank" class="sina-btn sina-btn-secondary">🖨️ چاپ</a>
                        <button class="sina-btn sina-btn-danger sina-admin-delete-app" data-id="<?php echo esc_attr($app->id); ?>">🗑️</button>
                        <a href="<?php echo admin_url('admin.php?page=sina-booking-appointments&tracking='.$app->tracking_code); ?>" class="sina-btn sina-btn-primary">👁️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

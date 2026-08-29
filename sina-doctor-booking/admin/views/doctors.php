<?php if (!defined('ABSPATH')) exit;
global $wpdb;
$table = $wpdb->prefix . 'sina_doctors';
$doctors = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
$users = get_users(['role__in' => ['sina_doctor', 'sina_secretary', 'administrator']]);
?>
<div class="wrap sina-admin-wrap">
    <div class="sina-admin-header">
        <h1>👨‍⚕️ مدیریت پزشکان</h1>
        <button class="sina-btn" style="background:white; color:#0ea5e9;" data-modal="#doctorModal" onclick="document.getElementById('sinaDoctorForm').reset(); document.getElementById('doctor_id').value='';">➕ افزودن پزشک</button>
    </div>

    <div class="sina-admin-card">
        <table class="sina-admin-table">
            <thead>
                <tr><th>ID</th><th>نام</th><th>تخصص</th><th>تلفن</th><th>ویزیت</th><th>منشی</th><th>وضعیت</th><th>عملیات</th></tr>
            </thead>
            <tbody>
                <?php foreach ($doctors as $doc):
                    $sec_user = $doc->secretary_user_id ? get_userdata($doc->secretary_user_id) : null;
                ?>
                <tr>
                    <td><?php echo esc_html($doc->id); ?></td>
                    <td><strong><?php echo esc_html($doc->name); ?></strong><br><small><?php echo esc_html($doc->slug); ?></small></td>
                    <td><?php echo esc_html($doc->specialty); ?></td>
                    <td><?php echo esc_html($doc->phone); ?></td>
                    <td><?php echo number_format($doc->consultation_fee); ?> ت</td>
                    <td><?php echo $sec_user ? esc_html($sec_user->display_name) : '-'; ?></td>
                    <td><span class="sina-badge <?php echo $doc->is_active ? 'sina-badge-success' : 'sina-badge-danger'; ?>"><?php echo $doc->is_active ? 'فعال' : 'غیرفعال'; ?></span></td>
                    <td>
                        <button class="sina-btn sina-btn-secondary sina-edit-doctor" data-doctor='<?php echo json_encode($doc, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>✏️ ویرایش</button>
                        <button class="sina-btn sina-btn-danger sina-delete-doctor" data-id="<?php echo esc_attr($doc->id); ?>">🗑️ حذف</button>
                        <a href="<?php echo admin_url('admin.php?page=sina-booking-schedules&doctor_id='.$doc->id); ?>" class="sina-btn sina-btn-primary">📅 برنامه</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="sina-modal" id="doctorModal">
    <div class="sina-modal-content" style="max-width:700px;">
        <h3>👨‍⚕️ افزودن / ویرایش پزشک</h3>
        <form id="sinaDoctorForm">
            <input type="hidden" name="id" id="doctor_id">
            <div class="sina-form-row">
                <div>
                    <label>نام پزشک *</label>
                    <input type="text" name="name" id="doctor_name" class="sina-input" required placeholder="دکتر ...">
                </div>
                <div>
                    <label>تخصص *</label>
                    <input type="text" name="specialty" id="doctor_specialty" class="sina-input" required placeholder="متخصص قلب">
                </div>
            </div>
            <div class="sina-form-row">
                <div>
                    <label>تلفن</label>
                    <input type="text" name="phone" id="doctor_phone" class="sina-input" placeholder="09123456789">
                </div>
                <div>
                    <label>ایمیل</label>
                    <input type="email" name="email" id="doctor_email" class="sina-input">
                </div>
            </div>
            <div class="sina-form-row">
                <div>
                    <label>هزینه ویزیت (تومان)</label>
                    <input type="number" name="consultation_fee" id="doctor_fee" class="sina-input" value="350000">
                </div>
                <div>
                    <label>آواتار URL</label>
                    <input type="url" name="avatar_url" id="doctor_avatar" class="sina-input" placeholder="https://...">
                </div>
            </div>
            <div class="sina-form-row">
                <div>
                    <label>کاربر پزشک (اختیاری)</label>
                    <select name="user_id" id="doctor_user_id" class="sina-select">
                        <option value="">-- انتخاب --</option>
                        <?php foreach ($users as $u): ?>
                        <option value="<?php echo esc_attr($u->ID); ?>"><?php echo esc_html($u->display_name . ' (' . $u->user_login . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>کاربر منشی (اختیاری)</label>
                    <select name="secretary_user_id" id="doctor_secretary_id" class="sina-select">
                        <option value="">-- انتخاب --</option>
                        <?php foreach ($users as $u): ?>
                        <option value="<?php echo esc_attr($u->ID); ?>"><?php echo esc_html($u->display_name . ' (' . $u->user_login . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div>
                <label>بیوگرافی</label>
                <textarea name="bio" id="doctor_bio" class="sina-textarea" rows="3"></textarea>
            </div>
            <div>
                <label>اطلاعیه برای این پزشک (نمایش در سایت)</label>
                <textarea name="announcement" id="doctor_announcement" class="sina-textarea" rows="2" placeholder="مثلا: دکتر فلان تاریخ حضور ندارند"></textarea>
            </div>
            <div style="margin-top:12px;">
                <label><input type="checkbox" name="is_active" value="1" checked> فعال</label>
            </div>
            <div style="display:flex; gap:8px; margin-top:16px; justify-content:flex-end;">
                <button type="button" class="sina-btn sina-btn-secondary sina-modal-close">انصراف</button>
                <button type="submit" class="sina-btn sina-btn-primary">💾 ذخیره پزشک</button>
            </div>
        </form>
    </div>
</div>

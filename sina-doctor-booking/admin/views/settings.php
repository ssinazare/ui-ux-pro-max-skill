<?php if (!defined('ABSPATH')) exit;
$settings = get_option('sina_booking_settings', [
    'booking_fee' => 5000,
    'currency' => 'تومان',
    'enable_woocommerce' => 0,
    'thermal_width' => '80mm',
    'clinic_name' => 'کلینیک سینا',
    'clinic_address' => '',
    'clinic_phone' => '',
]);
$woo_exists = class_exists('WooCommerce');
?>
<div class="wrap sina-admin-wrap">
    <div class="sina-admin-header">
        <h1>⚙️ تنظیمات نوبت دهی</h1>
    </div>

    <form id="sinaSettingsForm">
        <div class="sina-admin-card">
            <h3>💰 تنظیمات مالی</h3>
            <div class="sina-form-row">
                <div>
                    <label>مبلغ بیعانه رزرو نوبت (تومان)</label>
                    <input type="number" name="booking_fee" value="<?php echo esc_attr($settings['booking_fee']); ?>" class="sina-input">
                    <small style="color:#64748b;">مبلغی که بیمار برای رزرو آنلاین پرداخت می‌کند (مثلا 5000 تومان)</small>
                </div>
                <div>
                    <label>واحد پول</label>
                    <input type="text" name="currency" value="<?php echo esc_attr($settings['currency']); ?>" class="sina-input">
                </div>
            </div>
            <div style="margin-top:16px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="enable_woocommerce" value="1" <?php checked($settings['enable_woocommerce'], 1); ?> <?php disabled(!$woo_exists); ?>>
                    فعال‌سازی پرداخت ووکامرس
                    <?php if (!$woo_exists): ?><small style="color:red;">(ووکامرس نصب نیست)</small><?php endif; ?>
                </label>
                <small style="color:#64748b; display:block; margin-top:4px;">اگر فعال باشد، هنگام رزرو سفارش ووکامرس ایجاد می‌شود و بیمار به درگاه پرداخت هدایت می‌شود</small>
            </div>
        </div>

        <div class="sina-admin-card">
            <h3>🏥 اطلاعات کلینیک (برای چاپ رسید)</h3>
            <div class="sina-form-row">
                <div>
                    <label>نام کلینیک / مطب</label>
                    <input type="text" name="clinic_name" value="<?php echo esc_attr($settings['clinic_name']); ?>" class="sina-input" placeholder="کلینیک سینا">
                </div>
                <div>
                    <label>تلفن کلینیک</label>
                    <input type="text" name="clinic_phone" value="<?php echo esc_attr($settings['clinic_phone']); ?>" class="sina-input" placeholder="021-12345678">
                </div>
            </div>
            <div style="margin-top:12px;">
                <label>آدرس کلینیک</label>
                <input type="text" name="clinic_address" value="<?php echo esc_attr($settings['clinic_address']); ?>" class="sina-input" placeholder="تهران، خیابان...">
            </div>
        </div>

        <div class="sina-admin-card">
            <h3>🖨️ تنظیمات چاپ حرارتی</h3>
            <div class="sina-form-row">
                <div>
                    <label>عرض کاغذ حرارتی</label>
                    <select name="thermal_width" class="sina-select">
                        <option value="58mm" <?php selected($settings['thermal_width'], '58mm'); ?>>58mm (کوچک)</option>
                        <option value="80mm" <?php selected($settings['thermal_width'], '80mm'); ?>>80mm (استاندارد)</option>
                    </select>
                </div>
                <div>
                    <label>تست چاپ</label>
                    <a href="<?php echo home_url('/sina-print/SINA-TEST/'); ?>" target="_blank" class="sina-btn sina-btn-secondary" style="width:100%; justify-content:center; padding:10px;">🖨️ پیش‌نمایش رسید حرارتی</a>
                </div>
            </div>
            <div style="background:#f0f9ff; padding:12px; border-radius:8px; margin-top:12px; font-size:12px; line-height:1.8;">
                <strong>نکات پرینتر حرارتی:</strong><br>
                • پرینترهای حرارتی معمولا 80mm یا 58mm هستند<br>
                • در تنظیمات پرینتر، سایز را روی 80mm بگذارید<br>
                • مرورگر کروم بهترین سازگاری را با چاپ حرارتی دارد<br>
                • برای چاپ خودکار، به لینک رسید ?autoprint=1 اضافه کنید
            </div>
        </div>

        <div class="sina-admin-card">
            <h3>🔗 شورت‌کدها و المنتور</h3>
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap:12px; font-size:13px;">
                <div style="background:#f8fafc; padding:12px; border-radius:8px;">
                    <strong>[sina_booking]</strong><br>
                    <small>صفحه اصلی نوبت‌دهی (لیست پزشکان + رزرو)</small>
                </div>
                <div style="background:#f8fafc; padding:12px; border-radius:8px;">
                    <strong>[sina_patient_panel]</strong><br>
                    <small>پنل بیمار - مشاهده نوبت‌های خود</small>
                </div>
                <div style="background:#f8fafc; padding:12px; border-radius:8px;">
                    <strong>[sina_secretary_panel]</strong><br>
                    <small>پنل منشی - مدیریت نوبت‌ها، محاسبه هزینه، چاپ</small>
                </div>
                <div style="background:#f8fafc; padding:12px; border-radius:8px;">
                    <strong>[sina_doctors_list]</strong><br>
                    <small>فقط لیست پزشکان</small>
                </div>
                <div style="background:#f8fafc; padding:12px; border-radius:8px;">
                    <strong>[sina_tracking]</strong><br>
                    <small>فرم پیگیری با کد رهگیری</small>
                </div>
            </div>
            <div style="margin-top:12px; font-size:12px; color:#64748b;">
                💡 المنتور: در ویرایشگر المنتور، دسته "نوبت دهی سینا" را ببینید - 3 ویجت اختصاصی دارد
            </div>
        </div>

        <div style="text-align:center; margin:20px 0;">
            <button type="submit" class="sina-btn sina-btn-primary" style="padding:12px 32px; font-size:14px;">💾 ذخیره تنظیمات</button>
        </div>
    </form>
</div>

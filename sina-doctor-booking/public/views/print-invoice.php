<?php
if (!defined('ABSPATH')) {
    // Direct access fallback
    require_once dirname(__DIR__, 3) . '/../../wp-load.php';
}
$code = get_query_var('sina_print_code') ?? $_GET['code'] ?? '';
if (empty($code)) {
    $code = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
}
$code = sanitize_text_field($code);

// Handle test print
$is_test = ($code === 'SINA-TEST');
if ($is_test) {
    $appointment = (object)[
        'tracking_code' => 'SINA-TEST',
        'patient_name' => 'علی احمدی (نمونه)',
        'patient_phone' => '09123456789',
        'patient_national_id' => '0012345678',
        'patient_age' => 30,
        'patient_gender' => 'male',
        'slot_date' => date('Y-m-d', strtotime('+2 days')),
        'slot_time' => '10:30:00',
        'status' => 'confirmed',
        'booking_fee' => 5000,
        'total_fee' => 500000,
        'paid_amount' => 5000,
        'services' => json_encode([['title'=>'نوار قلب','price'=>150000]]),
        'notes' => 'تست چاپ',
        'secretary_notes' => '',
        'doctor_id' => 0,
    ];
    $doctor = (object)['name'=>'دکتر سارا احمدی','specialty'=>'متخصص قلب','consultation_fee'=>350000];
    $jalali = Sina_Booking_Schedule::format_jalali($appointment->slot_date);
    $services = json_decode($appointment->services, true) ?: [];
    $settings = get_option('sina_booking_settings', []);
    $clinic_name = $settings['clinic_name'] ?? 'کلینیک سینا';
    $clinic_phone = $settings['clinic_phone'] ?? '021-12345678';
    $clinic_address = $settings['clinic_address'] ?? 'تهران، خیابان ولیعصر';
} else {
    $appointment = Sina_Booking_Appointment::get_by_tracking_code($code);
    if (!$appointment) {
        wp_die('نوبت یافت نشد - کد رهگیری نامعتبر است: ' . esc_html($code));
    }
    $doctor = Sina_Booking_Doctor::get_by_id($appointment->doctor_id);
    $settings = get_option('sina_booking_settings', []);
    $clinic_name = $settings['clinic_name'] ?? 'کلینیک سینا';
    $clinic_phone = $settings['clinic_phone'] ?? '';
    $clinic_address = $settings['clinic_address'] ?? '';
    $jalali = Sina_Booking_Schedule::format_jalali($appointment->slot_date);
    $services = json_decode($appointment->services, true) ?: [];
}

$status_labels = ['pending'=>'در انتظار','confirmed'=>'تایید شده','visited'=>'ویزیت شده','cancelled'=>'لغو شده','no_show'=>'عدم مراجعه'];
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>رسید نوبت - <?php echo esc_html($code); ?></title>
<style>
    @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
    * { margin:0; padding:0; box-sizing:border-box; }
    body {
        font-family: 'Vazirmatn', Tahoma, sans-serif;
        background: #f1f5f9;
        display: flex;
        justify-content: center;
        padding: 20px;
        direction: rtl;
    }
    .print-container {
        display: flex;
        gap: 30px;
        align-items: flex-start;
        flex-wrap: wrap;
        justify-content: center;
    }
    /* Thermal 80mm */
    .thermal-receipt {
        width: 80mm;
        background: white;
        padding: 6mm;
        font-size: 11px;
        line-height: 1.5;
        color: #000;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        border: 1px dashed #ccc;
    }
    .thermal-receipt h1 {
        font-size: 14px;
        text-align: center;
        margin-bottom: 4px;
        font-weight: 800;
    }
    .thermal-receipt .center { text-align: center; }
    .thermal-receipt .small { font-size: 9px; color: #555; }
    .thermal-receipt .divider {
        border-top: 1px dashed #000;
        margin: 8px 0;
    }
    .thermal-receipt .row {
        display: flex;
        justify-content: space-between;
        margin: 3px 0;
    }
    .thermal-receipt .bold { font-weight: 700; }
    .thermal-receipt .big { font-size: 13px; }
    .thermal-receipt .tracking {
        font-size: 16px;
        font-weight: 900;
        letter-spacing: 1px;
        border: 2px solid #000;
        padding: 6px;
        text-align: center;
        margin: 10px 0;
    }
    .thermal-receipt .barcode {
        text-align: center;
        font-family: monospace;
        font-size: 10px;
        margin: 8px 0;
        letter-spacing: 2px;
    }
    /* A4 */
    .a4-invoice {
        width: 210mm;
        background: white;
        padding: 15mm;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        font-size: 13px;
    }
    .a4-invoice .header {
        display: flex;
        justify-content: space-between;
        border-bottom: 3px solid #0ea5e9;
        padding-bottom: 15px;
        margin-bottom: 20px;
    }
    .a4-invoice .logo { font-size: 22px; font-weight: 800; color: #0ea5e9; }
    .a4-invoice table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    .a4-invoice th, .a4-invoice td { border: 1px solid #e2e8f0; padding: 10px; text-align: right; }
    .a4-invoice th { background: #f8fafc; font-weight: 700; }
    .no-print { margin-bottom: 20px; text-align: center; }
    .btn {
        padding: 10px 20px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-family: inherit;
        font-weight: 600;
        margin: 5px;
        text-decoration: none;
        display: inline-block;
    }
    .btn-primary { background: #0ea5e9; color: white; }
    .btn-secondary { background: #e2e8f0; color: #334155; }
    @media print {
        body { background: white; padding: 0; }
        .no-print { display: none !important; }
        .print-container { gap: 0; }
        .a4-invoice { box-shadow: none; width: 100%; padding: 10mm; }
        .thermal-receipt { box-shadow: none; border: none; width: 80mm; }
        /* Only print thermal if user wants thermal */
        body.thermal-only .a4-invoice { display: none; }
        body.a4-only .thermal-receipt { display: none; }
    }
</style>
</head>
<body>
<div style="width:100%; max-width:1200px;">
    <div class="no-print">
        <h2 style="margin-bottom:15px;">🖨️ چاپ رسید نوبت - <?php echo esc_html($code); ?></h2>
        <button class="btn btn-primary" onclick="window.print()">🖨️ چاپ هر دو نسخه</button>
        <button class="btn btn-primary" onclick="document.body.className='thermal-only'; window.print(); document.body.className='';">🧾 فقط چاپ حرارتی 80mm</button>
        <button class="btn btn-primary" onclick="document.body.className='a4-only'; window.print(); document.body.className='';">📄 فقط چاپ A4</button>
        <a href="javascript:window.close()" class="btn btn-secondary">✕ بستن</a>
        <a href="<?php echo home_url('/sina-booking/'); ?>" class="btn btn-secondary">🏠 بازگشت به سایت</a>
        <div style="margin-top:10px; font-size:12px; color:#64748b;">برای پرینتر حرارتی، سایز کاغذ را 80mm تنظیم کنید</div>
    </div>

    <div class="print-container">
        <!-- Thermal Receipt 80mm -->
        <div class="thermal-receipt sina-print-area">
            <div class="center">
                <h1><?php echo esc_html($clinic_name); ?></h1>
                <div class="small"><?php echo esc_html($clinic_address); ?></div>
                <?php if ($clinic_phone): ?><div class="small">☎ <?php echo esc_html($clinic_phone); ?></div><?php endif; ?>
            </div>
            <div class="divider"></div>
            <div class="center small">رسید نوبت دهی</div>
            <div class="tracking"><?php echo esc_html($code); ?></div>
            <div class="divider"></div>
            
            <div class="row"><span>بیمار:</span><span class="bold"><?php echo esc_html($appointment->patient_name); ?></span></div>
            <div class="row"><span>موبایل:</span><span><?php echo esc_html($appointment->patient_phone); ?></span></div>
            <?php if ($appointment->patient_national_id): ?>
            <div class="row"><span>کد ملی:</span><span><?php echo esc_html($appointment->patient_national_id); ?></span></div>
            <?php endif; ?>
            
            <div class="divider"></div>
            
            <div class="row"><span>پزشک:</span><span class="bold"><?php echo esc_html($doctor ? $doctor->name : ''); ?></span></div>
            <div class="row"><span>تخصص:</span><span><?php echo esc_html($doctor ? $doctor->specialty : ''); ?></span></div>
            
            <div class="divider"></div>
            
            <div class="row"><span>تاریخ:</span><span class="bold"><?php echo esc_html($jalali); ?></span></div>
            <div class="row small"><span></span><span><?php echo esc_html($appointment->slot_date); ?></span></div>
            <div class="row"><span>ساعت:</span><span class="bold big"><?php echo esc_html(substr($appointment->slot_time,0,5)); ?></span></div>
            <div class="row"><span>وضعیت:</span><span><?php echo esc_html($status_labels[$appointment->status] ?? $appointment->status); ?></span></div>
            
            <div class="divider"></div>
            
            <div class="row"><span>بیعانه رزرو:</span><span><?php echo number_format($appointment->booking_fee); ?> تومان</span></div>
            <div class="row"><span>ویزیت:</span><span><?php echo number_format($doctor ? $doctor->consultation_fee : 0); ?> تومان</span></div>
            
            <?php if (!empty($services)): ?>
            <div style="margin:6px 0; font-weight:700;">خدمات:</div>
            <?php foreach ($services as $srv): ?>
            <div class="row small"><span>- <?php echo esc_html($srv['title']); ?></span><span><?php echo number_format($srv['price']); ?></span></div>
            <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="divider"></div>
            
            <div class="row big bold"><span>جمع کل:</span><span><?php echo number_format($appointment->total_fee); ?> تومان</span></div>
            <div class="row"><span>پرداختی:</span><span><?php echo number_format($appointment->paid_amount); ?> تومان</span></div>
            <div class="row bold"><span>مانده:</span><span><?php echo number_format($appointment->total_fee - $appointment->paid_amount); ?> تومان</span></div>
            
            <div class="divider"></div>
            
            <div class="center small">
                لطفا 15 دقیقه قبل حضور داشته باشید<br>
                همراه داشتن کارت ملی الزامی است<br>
                <?php echo current_time('Y/m/d H:i'); ?>
            </div>
            
            <div class="barcode">*<?php echo esc_html($code); ?>*</div>
            <div class="center small">با تشکر از انتخاب شما ❤️</div>
        </div>

        <!-- A4 Invoice -->
        <div class="a4-invoice">
            <div class="header">
                <div>
                    <div class="logo"><?php echo esc_html($clinic_name); ?></div>
                    <div style="font-size:12px; color:#64748b; margin-top:4px;">
                        <?php echo esc_html($clinic_address); ?><br>
                        <?php if ($clinic_phone): ?>تلفن: <?php echo esc_html($clinic_phone); ?><?php endif; ?>
                    </div>
                </div>
                <div style="text-align:left;">
                    <div style="font-size:18px; font-weight:800;">رسید نوبت پزشکی</div>
                    <div style="font-size:12px; color:#64748b;">تاریخ صدور: <?php echo current_time('Y/m/d H:i'); ?></div>
                    <div style="margin-top:8px; background:#f0f9ff; padding:6px 12px; border-radius:6px; border:1px solid #0ea5e9; font-weight:700; letter-spacing:1px;"><?php echo esc_html($code); ?></div>
                </div>
            </div>

            <table>
                <tr><th colspan="2" style="background:#0ea5e9; color:white;">اطلاعات بیمار</th><th colspan="2" style="background:#0ea5e9; color:white;">اطلاعات نوبت</th></tr>
                <tr>
                    <td style="width:15%; background:#f8fafc;">نام بیمار</td><td><?php echo esc_html($appointment->patient_name); ?></td>
                    <td style="width:15%; background:#f8fafc;">پزشک</td><td><?php echo esc_html($doctor ? $doctor->name : ''); ?></td>
                </tr>
                <tr>
                    <td style="background:#f8fafc;">موبایل</td><td><?php echo esc_html($appointment->patient_phone); ?></td>
                    <td style="background:#f8fafc;">تخصص</td><td><?php echo esc_html($doctor ? $doctor->specialty : ''); ?></td>
                </tr>
                <tr>
                    <td style="background:#f8fafc;">کد ملی</td><td><?php echo esc_html($appointment->patient_national_id ?: '-'); ?></td>
                    <td style="background:#f8fafc;">تاریخ نوبت</td><td><strong><?php echo esc_html($jalali); ?></strong> (<?php echo esc_html($appointment->slot_date); ?>)</td>
                </tr>
                <tr>
                    <td style="background:#f8fafc;">سن / جنسیت</td><td><?php echo esc_html($appointment->patient_age ?: '-'); ?> / <?php echo esc_html($appointment->patient_gender === 'male' ? 'آقا' : ($appointment->patient_gender === 'female' ? 'خانم' : '-')); ?></td>
                    <td style="background:#f8fafc;">ساعت</td><td><strong><?php echo esc_html(substr($appointment->slot_time,0,5)); ?></strong> - وضعیت: <?php echo esc_html($status_labels[$appointment->status] ?? $appointment->status); ?></td>
                </tr>
                <?php if ($appointment->notes): ?>
                <tr><td style="background:#f8fafc;">توضیحات بیمار</td><td colspan="3"><?php echo esc_html($appointment->notes); ?></td></tr>
                <?php endif; ?>
                <?php if ($appointment->secretary_notes): ?>
                <tr><td style="background:#f8fafc;">یادداشت منشی</td><td colspan="3"><?php echo esc_html($appointment->secretary_notes); ?></td></tr>
                <?php endif; ?>
            </table>

            <h3 style="margin:20px 0 10px; font-size:14px;">💰 صورتحساب خدمات</h3>
            <table>
                <thead><tr><th>ردیف</th><th>شرح خدمات</th><th>مبلغ (تومان)</th></tr></thead>
                <tbody>
                    <tr><td>1</td><td>ویزیت <?php echo esc_html($doctor ? $doctor->name : ''); ?></td><td><?php echo number_format($doctor ? $doctor->consultation_fee : 0); ?></td></tr>
                    <?php $i=2; foreach ($services as $srv): ?>
                    <tr><td><?php echo $i++; ?></td><td><?php echo esc_html($srv['title']); ?></td><td><?php echo number_format($srv['price']); ?></td></tr>
                    <?php endforeach; ?>
                    <tr style="background:#f0fdf4; font-weight:700;"><td colspan="2">جمع کل</td><td><?php echo number_format($appointment->total_fee); ?> تومان</td></tr>
                    <tr><td colspan="2">بیعانه رزرو (پرداخت آنلاین)</td><td><?php echo number_format($appointment->booking_fee); ?> تومان</td></tr>
                    <tr><td colspan="2">مبلغ پرداخت شده</td><td><?php echo number_format($appointment->paid_amount); ?> تومان</td></tr>
                    <tr style="background:#fef2f2; font-weight:700;"><td colspan="2">مانده قابل پرداخت در مطب</td><td><?php echo number_format(max(0, $appointment->total_fee - $appointment->paid_amount)); ?> تومان</td></tr>
                </tbody>
            </table>

            <div style="margin-top:20px; padding:12px; background:#fffbeb; border:1px solid #fde68a; border-radius:8px; font-size:12px;">
                <strong>نکات مهم:</strong><br>
                • لطفا 15 دقیقه قبل از نوبت در مطب حضور داشته باشید<br>
                • همراه داشتن کارت ملی و این رسید الزامی است<br>
                • در صورت عدم امکان مراجعه، حداقل 24 ساعت قبل اطلاع دهید<br>
                • این رسید تا زمان ویزیت معتبر است
            </div>

            <div style="display:flex; justify-content:space-between; margin-top:40px; font-size:12px;">
                <div style="text-align:center;"><div style="border-top:1px solid #000; width:120px; padding-top:8px; margin-top:40px;">مهر و امضای منشی</div></div>
                <div style="text-align:center;"><div style="border-top:1px solid #000; width:120px; padding-top:8px; margin-top:40px;">امضای بیمار</div></div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto print if ?autoprint=1
if (new URLSearchParams(window.location.search).get('autoprint') === '1') {
    window.onload = () => setTimeout(() => window.print(), 500);
}
</script>
</body>
</html>

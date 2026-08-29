<?php if (!defined('ABSPATH')) exit;
$doctor_id = intval($atts['doctor_id'] ?? 0);
$doctors = $doctor_id ? [Sina_Booking_Doctor::get_by_id($doctor_id)] : Sina_Booking_Doctor::get_all(true);
$settings = get_option('sina_booking_settings', []);
$booking_fee = $settings['booking_fee'] ?? 5000;
$announcements = Sina_Booking_Doctor::get_announcements($doctor_id ?: null);
?>
<div class="sina-booking-wrapper" data-doctor-id="<?php echo esc_attr($doctor_id); ?>">
    
    <?php if (!empty($announcements)): ?>
    <div class="sina-announcements">
        <?php foreach ($announcements as $ann): ?>
        <div class="sina-announcement <?php echo esc_attr($ann->type); ?>" data-doctor="<?php echo esc_attr($ann->doctor_id); ?>">
            <strong><?php echo esc_html($ann->title); ?>:</strong> <?php echo esc_html($ann->message); ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Steps -->
    <div class="sina-booking-steps">
        <div class="sina-step active" data-step="1"><span class="sina-step-number">1</span> انتخاب پزشک</div>
        <div class="sina-step" data-step="2"><span class="sina-step-number">2</span> انتخاب روز</div>
        <div class="sina-step" data-step="3"><span class="sina-step-number">3</span> انتخاب ساعت</div>
        <div class="sina-step" data-step="4"><span class="sina-step-number">4</span> ثبت اطلاعات</div>
        <div class="sina-step" data-step="5"><span class="sina-step-number">5</span> تایید نهایی</div>
    </div>

    <!-- Doctors -->
    <div class="sina-section" id="sinaDoctorsSection">
        <h2 class="sina-section-title">👨‍⚕️ پزشک خود را انتخاب کنید</h2>
        <div class="sina-doctors-grid">
            <?php foreach ($doctors as $doctor): if (!$doctor) continue; ?>
            <div class="sina-doctor-card <?php echo $doctor_id == $doctor->id ? 'active' : ''; ?>" data-id="<?php echo esc_attr($doctor->id); ?>">
                <?php if ($doctor->avatar_url): ?>
                    <img src="<?php echo esc_url($doctor->avatar_url); ?>" class="sina-doctor-avatar" alt="">
                <?php else: ?>
                    <div class="sina-doctor-avatar-placeholder"><?php echo esc_html(mb_substr($doctor->name, 0, 1)); ?></div>
                <?php endif; ?>
                <h3 class="sina-doctor-name"><?php echo esc_html($doctor->name); ?></h3>
                <div style="text-align:center;"><span class="sina-doctor-specialty"><?php echo esc_html($doctor->specialty); ?></span></div>
                <div class="sina-doctor-fee">ویزیت: <strong><?php echo number_format($doctor->consultation_fee); ?> تومان</strong></div>
                <?php if ($doctor->announcement): ?>
                <div class="sina-alert sina-alert-warning" style="font-size:12px; padding:8px; margin-top:8px;"><?php echo esc_html($doctor->announcement); ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Dates -->
    <div class="sina-section" id="sinaDatesSection" style="<?php echo $doctor_id ? '' : 'display:none;'; ?>">
        <h2 class="sina-section-title">📅 روز مورد نظر را انتخاب کنید</h2>
        <div id="sinaDatesGrid" class="sina-dates-grid">
            <div class="sina-loading"><div class="sina-spinner"></div>لطفا ابتدا پزشک را انتخاب کنید</div>
        </div>
    </div>

    <!-- Slots -->
    <div class="sina-section" id="sinaSlotsSection" style="display:none;">
        <h2 class="sina-section-title">⏰ ساعت نوبت را انتخاب کنید</h2>
        <div id="sinaSlotsGrid" class="sina-slots-grid"></div>
        <div class="sina-alert sina-alert-info" style="margin-top:16px;">
            💡 هزینه رزرو نوبت: <strong><?php echo number_format($booking_fee); ?> تومان</strong> (در صورت عدم مراجعه عودت داده نمی‌شود)
        </div>
    </div>

    <!-- Form -->
    <div class="sina-section" id="sinaFormSection" style="display:none;">
        <h2 class="sina-section-title">📝 اطلاعات بیمار</h2>
        <form id="sinaBookingForm">
            <div class="sina-form-grid">
                <div class="sina-form-group">
                    <label>نام و نام خانوادگی <span style="color:red;">*</span></label>
                    <input type="text" name="patient_name" class="sina-input" required placeholder="مثال: علی احمدی">
                </div>
                <div class="sina-form-group">
                    <label>شماره موبایل <span style="color:red;">*</span></label>
                    <input type="tel" name="patient_phone" class="sina-input" required placeholder="09123456789" pattern="09[0-9]{9}">
                </div>
                <div class="sina-form-group">
                    <label>کد ملی</label>
                    <input type="text" name="patient_national_id" class="sina-input" placeholder="0012345678">
                </div>
                <div class="sina-form-group">
                    <label>ایمیل (اختیاری)</label>
                    <input type="email" name="patient_email" class="sina-input" placeholder="example@email.com">
                </div>
                <div class="sina-form-group">
                    <label>سن</label>
                    <input type="number" name="patient_age" class="sina-input" placeholder="30" min="0" max="120">
                </div>
                <div class="sina-form-group">
                    <label>جنسیت</label>
                    <select name="patient_gender" class="sina-select">
                        <option value="">انتخاب کنید</option>
                        <option value="male">آقا</option>
                        <option value="female">خانم</option>
                    </select>
                </div>
            </div>
            <div class="sina-form-group">
                <label>توضیحات / علائم (اختیاری)</label>
                <textarea name="notes" class="sina-textarea" rows="3" placeholder="توضیحات خود را بنویسید..."></textarea>
            </div>
            
            <div class="sina-alert sina-alert-info">
                <strong>مبلغ قابل پرداخت برای رزرو:</strong> <?php echo number_format($booking_fee); ?> تومان<br>
                <small>پس از ثبت، کد رهگیری برای شما صادر می‌شود. لطفا در زمان مراجعه کد را به همراه داشته باشید.</small>
            </div>

            <div style="text-align:center; margin-top:20px;">
                <button type="submit" class="sina-btn sina-btn-primary sina-btn-lg">
                    ✅ تایید و ثبت نوبت - پرداخت <?php echo number_format($booking_fee); ?> تومان
                </button>
            </div>
        </form>
    </div>

    <!-- Success -->
    <div class="sina-section" id="sinaSuccessSection" style="display:none;">
        <div class="sina-success-box">
            <div class="sina-success-icon">✓</div>
            <h2 style="color:#15803d; margin:0 0 10px;">نوبت شما با موفقیت ثبت شد!</h2>
            <p id="sinaSuccessMessage">کد رهگیری خود را یادداشت کنید و در زمان مراجعه به همراه داشته باشید</p>
            
            <div class="sina-tracking-code" id="sinaSuccessCode">SINA-XXXXXX</div>
            
            <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap; margin-top:20px;">
                <a href="#" id="sinaSuccessPrint" class="sina-btn sina-btn-primary" target="_blank">🖨️ چاپ رسید نوبت</a>
                <a href="#" id="sinaCheckoutLink" class="sina-btn sina-btn-success" style="display:none;">💳 پرداخت آنلاین</a>
                <a href="<?php echo home_url('/sina-patient-panel/'); ?>" class="sina-btn sina-btn-secondary">📋 مشاهده نوبت‌های من</a>
            </div>

            <div class="sina-alert sina-alert-warning" style="margin-top:20px; text-align:right;">
                <strong>نکات مهم:</strong><br>
                • لطفا 15 دقیقه قبل از نوبت در مطب حضور داشته باشید<br>
                • همراه داشتن کارت ملی الزامی است<br>
                • در صورت عدم امکان مراجعه، حداقل 24 ساعت قبل اطلاع دهید<br>
                • کد رهگیری: <span id="sinaSuccessTrack"></span>
            </div>
        </div>
    </div>

    <!-- Tracking quick -->
    <div class="sina-section">
        <h3 class="sina-section-title">🔍 پیگیری نوبت</h3>
        <?php echo do_shortcode('[sina_tracking]'); ?>
    </div>
</div>

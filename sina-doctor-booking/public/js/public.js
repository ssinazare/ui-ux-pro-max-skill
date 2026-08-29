jQuery(document).ready(function($){
    let selectedDoctor = null;
    let selectedDate = null;
    let selectedSlot = null;
    let bookingData = {};

    // Initialize if shortcode has doctor_id
    const wrapper = $('.sina-booking-wrapper');
    if (wrapper.length) {
        const initialDoctorId = wrapper.data('doctor-id');
        if (initialDoctorId) {
            selectedDoctor = initialDoctorId;
            loadDates(initialDoctorId);
            updateSteps(2);
            $('.sina-doctor-card[data-id="'+initialDoctorId+'"]').addClass('active');
        }
    }

    // Doctor selection
    $(document).on('click', '.sina-doctor-card', function(){
        const doctorId = $(this).data('id');
        $('.sina-doctor-card').removeClass('active');
        $(this).addClass('active');
        selectedDoctor = doctorId;
        selectedDate = null;
        selectedSlot = null;
        
        $('#sinaDatesSection').show();
        $('#sinaSlotsSection').hide();
        $('#sinaFormSection').hide();
        $('#sinaSuccessSection').hide();
        
        loadDates(doctorId);
        updateSteps(2);
        loadAnnouncements(doctorId);
        
        // Scroll to dates
        $('html, body').animate({ scrollTop: $('#sinaDatesSection').offset().top - 100 }, 500);
    });

    function loadAnnouncements(doctorId) {
        // Announcements already loaded server-side, but we can filter
        $('.sina-announcement').show();
        $('.sina-announcement[data-doctor]').each(function(){
            const dId = $(this).data('doctor');
            if (dId && dId != doctorId) $(this).hide();
        });
    }

    function loadDates(doctorId) {
        const container = $('#sinaDatesGrid');
        container.html('<div class="sina-loading"><div class="sina-spinner"></div>در حال بارگذاری روزها...</div>');
        
        $.post(sinaBooking.ajax_url, {
            action: 'sina_get_dates',
            doctor_id: doctorId,
            nonce: sinaBooking.nonce
        }, function(res){
            if (res.success) {
                const dates = res.data.dates;
                if (dates.length === 0) {
                    container.html('<div class="sina-alert sina-alert-warning">در حال حاضر نوبت خالی برای این پزشک وجود ندارد</div>');
                    return;
                }
                let html = '';
                dates.forEach(function(d){
                    html += `
                    <div class="sina-date-card" data-date="${d.date}">
                        <div class="sina-date-dayname">${d.day_name}</div>
                        <div class="sina-date-jalali">${d.jalali}</div>
                        <div class="sina-date-free">${d.free} نوبت خالی</div>
                    </div>`;
                });
                container.html(html);
            } else {
                container.html('<div class="sina-alert sina-alert-danger">'+res.data.message+'</div>');
            }
        }).fail(function(){
            container.html('<div class="sina-alert sina-alert-danger">خطا در ارتباط با سرور</div>');
        });
    }

    // Date selection
    $(document).on('click', '.sina-date-card', function(){
        if ($(this).hasClass('disabled')) return;
        $('.sina-date-card').removeClass('active');
        $(this).addClass('active');
        selectedDate = $(this).data('date');
        selectedSlot = null;
        
        $('#sinaSlotsSection').show();
        $('#sinaFormSection').hide();
        $('#sinaSuccessSection').hide();
        
        loadSlots(selectedDoctor, selectedDate);
        updateSteps(3);
        
        $('html, body').animate({ scrollTop: $('#sinaSlotsSection').offset().top - 100 }, 500);
    });

    function loadSlots(doctorId, date) {
        const container = $('#sinaSlotsGrid');
        container.html('<div class="sina-loading"><div class="sina-spinner"></div>در حال بارگذاری ساعت‌ها...</div>');
        
        $.post(sinaBooking.ajax_url, {
            action: 'sina_get_slots',
            doctor_id: doctorId,
            date: date,
            nonce: sinaBooking.nonce
        }, function(res){
            if (res.success) {
                const slots = res.data.slots;
                if (slots.length === 0) {
                    container.html('<div class="sina-alert sina-alert-warning">'+sinaBooking.i18n.no_slots+'</div>');
                    return;
                }
                let html = '';
                slots.forEach(function(s){
                    let cls = 'sina-slot';
                    let disabled = '';
                    if (s.is_booked) { cls += ' booked'; disabled = 'disabled'; }
                    else if (s.is_blocked) { cls += ' blocked'; disabled = 'disabled'; }
                    html += `<div class="${cls}" data-slot-id="${s.id}" ${disabled} title="${s.blocked_reason || ''}">
                        <div class="sina-slot-time">${s.time}</div>
                        ${s.is_booked ? '<small>رزرو شده</small>' : ''}
                        ${s.is_blocked ? '<small>مسدود</small>' : ''}
                    </div>`;
                });
                container.html(html);
            } else {
                container.html('<div class="sina-alert sina-alert-danger">'+res.data.message+'</div>');
            }
        });
    }

    // Slot selection
    $(document).on('click', '.sina-slot:not(.booked):not(.blocked)', function(){
        $('.sina-slot').removeClass('active');
        $(this).addClass('active');
        selectedSlot = $(this).data('slot-id');
        
        $('#sinaFormSection').show();
        $('#sinaSuccessSection').hide();
        updateSteps(4);
        
        $('html, body').animate({ scrollTop: $('#sinaFormSection').offset().top - 100 }, 500);
    });

    // Form submission
    $(document).on('submit', '#sinaBookingForm', function(e){
        e.preventDefault();
        
        const form = $(this);
        const btn = form.find('button[type="submit"]');
        const originalText = btn.text();
        
        // Validation
        let valid = true;
        form.find('[required]').each(function(){
            if (!$(this).val().trim()) {
                $(this).addClass('error');
                valid = false;
            } else {
                $(this).removeClass('error');
            }
        });
        
        if (!selectedSlot) {
            alert(sinaBooking.i18n.select_time);
            return;
        }
        
        if (!valid) {
            alert('لطفا تمام فیلدهای ستاره‌دار را پر کنید');
            return;
        }
        
        btn.prop('disabled', true).text(sinaBooking.i18n.loading);
        
        const data = {
            action: 'sina_book_appointment',
            nonce: sinaBooking.nonce,
            slot_id: selectedSlot,
            patient_name: form.find('[name="patient_name"]').val(),
            patient_phone: form.find('[name="patient_phone"]').val(),
            patient_national_id: form.find('[name="patient_national_id"]').val(),
            patient_email: form.find('[name="patient_email"]').val(),
            patient_age: form.find('[name="patient_age"]').val(),
            patient_gender: form.find('[name="patient_gender"]').val(),
            notes: form.find('[name="notes"]').val(),
        };
        
        $.post(sinaBooking.ajax_url, data, function(res){
            btn.prop('disabled', false).text(originalText);
            if (res.success) {
                showSuccess(res.data);
                updateSteps(5);
            } else {
                alert(res.data.message || 'خطا در ثبت نوبت');
                // Reload slots if booked
                if (selectedDoctor && selectedDate) {
                    loadSlots(selectedDoctor, selectedDate);
                }
            }
        }).fail(function(){
            btn.prop('disabled', false).text(originalText);
            alert('خطا در ارتباط با سرور');
        });
    });

    function showSuccess(data) {
        $('#sinaFormSection').hide();
        $('#sinaSuccessSection').show();
        $('#sinaSuccessCode').text(data.tracking_code);
        $('#sinaSuccessPrint').attr('href', data.print_url);
        $('#sinaSuccessTrack').attr('href', data.print_url);
        
        if (data.checkout_url) {
            $('#sinaCheckoutLink').attr('href', data.checkout_url).show();
            $('#sinaSuccessMessage').html('نوبت شما ثبت شد. برای تکمیل رزرو لطفا مبلغ بیعانه را پرداخت کنید.');
        } else {
            $('#sinaCheckoutLink').hide();
        }
        
        // Also show in modal or scroll
        $('html, body').animate({ scrollTop: $('#sinaSuccessSection').offset().top - 100 }, 500);
        
        // Clear selection for next booking? Keep for print
    }

    function updateSteps(step) {
        $('.sina-step').removeClass('active completed');
        $('.sina-step').each(function(){
            const s = $(this).data('step');
            if (s < step) $(this).addClass('completed');
            else if (s == step) $(this).addClass('active');
        });
    }

    // Print handling
    $(document).on('click', '.sina-print-btn', function(e){
        e.preventDefault();
        const url = $(this).attr('href');
        window.open(url, '_blank', 'width=400,height=600');
    });

    // Patient panel - cancel
    $(document).on('click', '.sina-cancel-appointment', function(){
        if (!confirm('آیا از لغو این نوبت اطمینان دارید؟')) return;
        const id = $(this).data('id');
        // Implement cancel via ajax if needed
        alert('برای لغو نوبت با منشی تماس بگیرید. کد رهگیری: ' + $(this).data('code'));
    });

    // Secretary panel filters
    $('#sinaSecretaryFilter').on('submit', function(e){
        e.preventDefault();
        const formData = $(this).serialize();
        // Reload via AJAX or page reload
        window.location.search = '?' + formData;
    });

    // Real-time search in secretary panel
    let searchTimeout;
    $('#sinaSearchInput').on('input', function(){
        clearTimeout(searchTimeout);
        const val = $(this).val();
        searchTimeout = setTimeout(function(){
            $('.sina-appointment-card').each(function(){
                const text = $(this).text().toLowerCase();
                if (text.includes(val.toLowerCase())) $(this).show();
                else $(this).hide();
            });
        }, 300);
    });
});

jQuery(document).ready(function($){
    // Modal handling
    $(document).on('click', '[data-modal]', function(){
        const target = $(this).data('modal');
        $(target).addClass('active');
    });
    $(document).on('click', '.sina-modal-close, .sina-modal', function(e){
        if (e.target === this) {
            $('.sina-modal').removeClass('active');
        }
    });

    // Doctor form
    $('#sinaDoctorForm').on('submit', function(e){
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).text('در حال ذخیره...');
        const data = $(this).serialize() + '&action=sina_admin_save_doctor&nonce=' + sinaAdmin.nonce;
        $.post(sinaAdmin.ajax_url, data, function(res){
            btn.prop('disabled', false).text('ذخیره');
            if (res.success) {
                alert(res.data.message);
                location.reload();
            } else {
                alert(res.data.message);
            }
        });
    });

    // Delete doctor
    $(document).on('click', '.sina-delete-doctor', function(){
        if (!confirm('آیا از حذف این پزشک اطمینان دارید؟ تمام نوبت‌های مرتبط نیز حذف خواهند شد!')) return;
        const id = $(this).data('id');
        $.post(sinaAdmin.ajax_url, {
            action: 'sina_admin_delete_doctor',
            id: id,
            nonce: sinaAdmin.nonce
        }, function(res){
            if (res.success) location.reload();
            else alert(res.data.message);
        });
    });

    // Edit doctor - populate form
    $(document).on('click', '.sina-edit-doctor', function(){
        const data = $(this).data('doctor');
        $('#doctor_id').val(data.id);
        $('#doctor_name').val(data.name);
        $('#doctor_specialty').val(data.specialty);
        $('#doctor_phone').val(data.phone);
        $('#doctor_email').val(data.email);
        $('#doctor_fee').val(data.consultation_fee);
        $('#doctor_bio').val(data.bio);
        $('#doctor_announcement').val(data.announcement);
        $('#doctor_avatar').val(data.avatar_url);
        $('#doctor_user_id').val(data.user_id);
        $('#doctor_secretary_id').val(data.secretary_user_id);
        $('#doctorModal').addClass('active');
    });

    // Schedule form
    $('#sinaScheduleForm').on('submit', function(e){
        e.preventDefault();
        const data = $(this).serialize() + '&action=sina_admin_save_schedule&nonce=' + sinaAdmin.nonce;
        $.post(sinaAdmin.ajax_url, data, function(res){
            if (res.success) {
                alert(res.data.message);
                location.reload();
            } else alert(res.data.message);
        });
    });

    $('.sina-delete-schedule').on('click', function(){
        if (!confirm('حذف شود؟')) return;
        const id = $(this).data('id');
        $.post(sinaAdmin.ajax_url, {
            action: 'sina_admin_delete_schedule',
            id: id,
            nonce: sinaAdmin.nonce
        }, function(res){ location.reload(); });
    });

    // Generate slots
    $('#sinaGenerateSlots').on('click', function(){
        const doctor_id = $('#generate_doctor_id').val();
        const days = $('#generate_days').val() || 30;
        const btn = $(this);
        btn.prop('disabled', true).text('در حال تولید...');
        $.post(sinaAdmin.ajax_url, {
            action: 'sina_admin_generate_slots',
            doctor_id: doctor_id,
            days: days,
            nonce: sinaAdmin.nonce
        }, function(res){
            btn.prop('disabled', false).text('تولید اسلات‌ها');
            alert(res.data.message);
        });
    });

    // Service form
    $('#sinaServiceForm').on('submit', function(e){
        e.preventDefault();
        const data = $(this).serialize() + '&action=sina_admin_save_service&nonce=' + sinaAdmin.nonce;
        $.post(sinaAdmin.ajax_url, data, function(res){
            if (res.success) location.reload();
            else alert(res.data.message);
        });
    });

    // Announcement form
    $('#sinaAnnouncementForm').on('submit', function(e){
        e.preventDefault();
        const data = $(this).serialize() + '&action=sina_admin_save_announcement&nonce=' + sinaAdmin.nonce;
        $.post(sinaAdmin.ajax_url, data, function(res){
            if (res.success) location.reload();
        });
    });

    // Settings
    $('#sinaSettingsForm').on('submit', function(e){
        e.preventDefault();
        const data = $(this).serialize() + '&action=sina_admin_save_settings&nonce=' + sinaAdmin.nonce;
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).text('در حال ذخیره...');
        $.post(sinaAdmin.ajax_url, data, function(res){
            btn.prop('disabled', false).text('ذخیره تنظیمات');
            if (res.success) alert(res.data.message);
        });
    });

    // Appointment quick actions in admin list
    $(document).on('click', '.sina-admin-delete-app', function(){
        if (!confirm('حذف شود؟')) return;
        const id = $(this).data('id');
        $.post(sinaAdmin.ajax_url, {
            action: 'sina_admin_delete_appointment',
            id: id,
            nonce: sinaAdmin.nonce
        }, function(res){
            if (res.success) location.reload();
        });
    });
});

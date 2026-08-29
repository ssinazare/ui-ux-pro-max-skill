<?php
if (!defined('ABSPATH')) exit;

class Sina_Booking_Database {

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Doctors table
        $table_doctors = $wpdb->prefix . 'sina_doctors';
        $sql_doctors = "CREATE TABLE $table_doctors (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            specialty varchar(255) NOT NULL,
            bio text DEFAULT NULL,
            avatar_url varchar(500) DEFAULT NULL,
            phone varchar(50) DEFAULT NULL,
            email varchar(255) DEFAULT NULL,
            secretary_user_id bigint(20) DEFAULT NULL,
            consultation_fee int DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            announcement text DEFAULT NULL,
            working_days text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql_doctors);

        // Services table
        $table_services = $wpdb->prefix . 'sina_services';
        $sql_services = "CREATE TABLE $table_services (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            doctor_id mediumint(9) DEFAULT NULL,
            title varchar(255) NOT NULL,
            description text DEFAULT NULL,
            price int NOT NULL DEFAULT 0,
            duration int DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY doctor_id (doctor_id)
        ) $charset_collate;";
        dbDelta($sql_services);

        // Schedules table - weekly template + exceptions
        $table_schedules = $wpdb->prefix . 'sina_schedules';
        $sql_schedules = "CREATE TABLE $table_schedules (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            doctor_id mediumint(9) NOT NULL,
            day_of_week tinyint(1) NOT NULL COMMENT '0=شنبه 6=جمعه',
            date_specific date DEFAULT NULL COMMENT 'for specific date override',
            start_time time NOT NULL,
            end_time time NOT NULL,
            slot_duration int NOT NULL DEFAULT 15 COMMENT 'minutes',
            max_patients_per_slot int DEFAULT 1,
            is_active tinyint(1) DEFAULT 1,
            announcement text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY doctor_id (doctor_id),
            KEY day_of_week (day_of_week),
            KEY date_specific (date_specific)
        ) $charset_collate;";
        dbDelta($sql_schedules);

        // Slots table - generated concrete slots
        $table_slots = $wpdb->prefix . 'sina_slots';
        $sql_slots = "CREATE TABLE $table_slots (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            doctor_id mediumint(9) NOT NULL,
            schedule_id mediumint(9) DEFAULT NULL,
            slot_date date NOT NULL,
            slot_time time NOT NULL,
            is_booked tinyint(1) DEFAULT 0,
            is_blocked tinyint(1) DEFAULT 0,
            blocked_reason varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY doctor_date_time (doctor_id, slot_date, slot_time),
            KEY slot_date (slot_date),
            KEY doctor_id (doctor_id)
        ) $charset_collate;";
        dbDelta($sql_slots);

        // Appointments table
        $table_appointments = $wpdb->prefix . 'sina_appointments';
        $sql_appointments = "CREATE TABLE $table_appointments (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            tracking_code varchar(50) NOT NULL,
            doctor_id mediumint(9) NOT NULL,
            slot_id mediumint(9) DEFAULT NULL,
            slot_date date NOT NULL,
            slot_time time NOT NULL,
            patient_user_id bigint(20) DEFAULT NULL,
            patient_name varchar(255) NOT NULL,
            patient_phone varchar(50) NOT NULL,
            patient_national_id varchar(20) DEFAULT NULL,
            patient_email varchar(255) DEFAULT NULL,
            patient_age int DEFAULT NULL,
            patient_gender varchar(20) DEFAULT NULL,
            booking_fee int DEFAULT 5000,
            total_fee int DEFAULT 0,
            paid_amount int DEFAULT 0,
            services text DEFAULT NULL COMMENT 'json array of service ids and prices',
            status varchar(30) DEFAULT 'pending' COMMENT 'pending, confirmed, visited, cancelled, no_show',
            payment_status varchar(30) DEFAULT 'unpaid' COMMENT 'unpaid, paid, partial',
            woo_order_id bigint(20) DEFAULT NULL,
            notes text DEFAULT NULL,
            secretary_notes text DEFAULT NULL,
            visit_date datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY tracking_code (tracking_code),
            KEY doctor_id (doctor_id),
            KEY slot_date (slot_date),
            KEY patient_phone (patient_phone),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_appointments);

        // Announcements
        $table_ann = $wpdb->prefix . 'sina_announcements';
        $sql_ann = "CREATE TABLE $table_ann (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            doctor_id mediumint(9) DEFAULT NULL COMMENT 'NULL = global',
            title varchar(255) NOT NULL,
            message text NOT NULL,
            type varchar(30) DEFAULT 'info' COMMENT 'info, warning, danger, success',
            start_date date DEFAULT NULL,
            end_date date DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY doctor_id (doctor_id)
        ) $charset_collate;";
        dbDelta($sql_ann);

        // Insert sample data if empty
        self::maybe_insert_sample_data();
    }

    private static function maybe_insert_sample_data() {
        global $wpdb;
        $table = $wpdb->prefix . 'sina_doctors';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($count > 0) return;

        // Sample doctors
        $doctors = [
            ['name' => 'دکتر سارا احمدی', 'specialty' => 'متخصص قلب و عروق', 'consultation_fee' => 350000],
            ['name' => 'دکتر علی رضایی', 'specialty' => 'متخصص مغز و اعصاب', 'consultation_fee' => 400000],
            ['name' => 'دکتر مریم حسینی', 'specialty' => 'متخصص زنان و زایمان', 'consultation_fee' => 300000],
        ];

        foreach ($doctors as $doc) {
            $wpdb->insert($table, [
                'name' => $doc['name'],
                'slug' => sanitize_title($doc['name']),
                'specialty' => $doc['specialty'],
                'bio' => 'پزشک متخصص با بیش از 10 سال سابقه درخشان',
                'consultation_fee' => $doc['consultation_fee'],
                'is_active' => 1,
            ]);
            $doctor_id = $wpdb->insert_id;

            // Sample schedules: Sat-Wed 9-13
            $table_sched = $wpdb->prefix . 'sina_schedules';
            for ($dow=0; $dow<=4; $dow++) {
                $wpdb->insert($table_sched, [
                    'doctor_id' => $doctor_id,
                    'day_of_week' => $dow,
                    'start_time' => '09:00:00',
                    'end_time' => '13:00:00',
                    'slot_duration' => 15,
                    'is_active' => 1,
                ]);
            }

            // Sample services
            $table_srv = $wpdb->prefix . 'sina_services';
            $wpdb->insert($table_srv, [
                'doctor_id' => $doctor_id,
                'title' => 'ویزیت اولیه',
                'price' => $doc['consultation_fee'],
                'duration' => 15,
            ]);
            $wpdb->insert($table_srv, [
                'doctor_id' => $doctor_id,
                'title' => 'نوار قلب',
                'price' => 150000,
                'duration' => 10,
            ]);
        }

        // Sample global announcement
        $table_ann = $wpdb->prefix . 'sina_announcements';
        $wpdb->insert($table_ann, [
            'title' => 'اطلاعیه مهم',
            'message' => 'لطفا 15 دقیقه قبل از نوبت در مطب حضور داشته باشید. همراه داشتن کارت ملی الزامی است.',
            'type' => 'info',
            'is_active' => 1,
        ]);

        // Generate slots for next 30 days
        Sina_Booking_Schedule::generate_slots_for_all_doctors(30);
    }

    public static function get_table($name) {
        global $wpdb;
        return $wpdb->prefix . 'sina_' . $name;
    }
}

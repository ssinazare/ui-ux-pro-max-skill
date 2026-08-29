<?php
if (!defined('ABSPATH')) exit;

class Sina_Booking_Schedule {

    public static function get_schedules($doctor_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'sina_schedules';
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE doctor_id = %d ORDER BY day_of_week, start_time", $doctor_id));
    }

    public static function save_schedule($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'sina_schedules';

        $defaults = [
            'doctor_id' => 0,
            'day_of_week' => 0,
            'date_specific' => null,
            'start_time' => '09:00:00',
            'end_time' => '13:00:00',
            'slot_duration' => 15,
            'max_patients_per_slot' => 1,
            'is_active' => 1,
            'announcement' => '',
        ];
        $data = wp_parse_args($data, $defaults);

        if (!empty($data['id'])) {
            $id = intval($data['id']);
            unset($data['id']);
            $wpdb->update($table, $data, ['id' => $id]);
            return $id;
        } else {
            $wpdb->insert($table, $data);
            return $wpdb->insert_id;
        }
    }

    public static function delete_schedule($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'sina_schedules';
        return $wpdb->delete($table, ['id' => $id]);
    }

    // Generate concrete slots from weekly template
    public static function generate_slots($doctor_id, $days_ahead = 30) {
        global $wpdb;
        $table_schedules = $wpdb->prefix . 'sina_schedules';
        $table_slots = $wpdb->prefix . 'sina_slots';

        $schedules = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_schedules WHERE doctor_id = %d AND is_active = 1 AND date_specific IS NULL", $doctor_id));
        if (empty($schedules)) return 0;

        $generated = 0;
        $today = new DateTime(current_time('Y-m-d'));

        for ($i = 0; $i < $days_ahead; $i++) {
            $date = clone $today;
            $date->modify("+$i days");
            $dow = self::convert_to_persian_dow($date); // 0=شنبه

            // Check specific date overrides (blocked)
            $date_str = $date->format('Y-m-d');
            $specific = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_schedules WHERE doctor_id = %d AND date_specific = %s", $doctor_id, $date_str));
            
            $is_blocked_day = false;
            $blocked_reason = '';
            foreach ($specific as $sp) {
                if (!$sp->is_active) {
                    $is_blocked_day = true;
                    $blocked_reason = $sp->announcement;
                }
            }
            if ($is_blocked_day) continue;

            foreach ($schedules as $sched) {
                if ((int)$sched->day_of_week !== $dow) continue;

                $start = DateTime::createFromFormat('H:i:s', $sched->start_time);
                $end = DateTime::createFromFormat('H:i:s', $sched->end_time);

                $current = clone $start;
                while ($current < $end) {
                    $slot_time = $current->format('H:i:s');
                    
                    // Check if slot exists
                    $exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM $table_slots WHERE doctor_id = %d AND slot_date = %s AND slot_time = %s",
                        $doctor_id, $date_str, $slot_time
                    ));

                    if (!$exists) {
                        $wpdb->insert($table_slots, [
                            'doctor_id' => $doctor_id,
                            'schedule_id' => $sched->id,
                            'slot_date' => $date_str,
                            'slot_time' => $slot_time,
                            'is_booked' => 0,
                            'is_blocked' => 0,
                        ]);
                        $generated++;
                    }

                    $current->modify("+{$sched->slot_duration} minutes");
                }
            }
        }

        return $generated;
    }

    public static function generate_slots_for_all_doctors($days = 30) {
        global $wpdb;
        $table = $wpdb->prefix . 'sina_doctors';
        $doctors = $wpdb->get_results("SELECT id FROM $table WHERE is_active = 1");
        $total = 0;
        foreach ($doctors as $doc) {
            $total += self::generate_slots($doc->id, $days);
        }
        return $total;
    }

    // Get available dates for a doctor (next 30 days with at least one free slot)
    public static function get_available_dates($doctor_id, $days_ahead = 30) {
        global $wpdb;
        $table_slots = $wpdb->prefix . 'sina_slots';
        $today = current_time('Y-m-d');
        $end_date = date('Y-m-d', strtotime("+$days_ahead days"));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT slot_date, COUNT(*) as total, SUM(CASE WHEN is_booked = 0 AND is_blocked = 0 THEN 1 ELSE 0 END) as free_count
            FROM $table_slots 
            WHERE doctor_id = %d AND slot_date BETWEEN %s AND %s
            GROUP BY slot_date
            HAVING free_count > 0
            ORDER BY slot_date ASC",
            $doctor_id, $today, $end_date
        ));

        return $results;
    }

    public static function get_slots_for_date($doctor_id, $date) {
        global $wpdb;
        $table_slots = $wpdb->prefix . 'sina_slots';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_slots WHERE doctor_id = %d AND slot_date = %s ORDER BY slot_time ASC",
            $doctor_id, $date
        ));
    }

    public static function block_slot($slot_id, $reason = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'sina_slots';
        return $wpdb->update($table, ['is_blocked' => 1, 'blocked_reason' => $reason], ['id' => $slot_id]);
    }

    public static function unblock_slot($slot_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'sina_slots';
        return $wpdb->update($table, ['is_blocked' => 0, 'blocked_reason' => ''], ['id' => $slot_id]);
    }

    private static function convert_to_persian_dow(DateTime $date) {
        // PHP w: 0=Sunday, 6=Saturday. Persian: 0=Saturday, 6=Friday
        $w = (int)$date->format('w');
        // Convert: Sat(6) -> 0, Sun(0)->1, Mon(1)->2, ... Fri(5)->6
        $map = [0 => 1, 1 => 2, 2 => 3, 3 => 4, 4 => 5, 5 => 6, 6 => 0];
        return $map[$w];
    }

    public static function persian_day_name($dow) {
        $names = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];
        return $names[$dow] ?? '';
    }

    public static function gregorian_to_jalali($gy, $gm, $gd) {
        // Simple conversion - use jDateTime if available, else approximate
        if (function_exists('gregorian_to_jalali')) {
            return gregorian_to_jalali($gy, $gm, $gd);
        }
        // Fallback approximate (for display only)
        $g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666 + (365*$gy) + (int)(($gy2+3)/4) - (int)(($gy2+99)/100) + (int)(($gy2+399)/400) + $gd + $g_d_m[$gm-1];
        $jy = -1595 + (33*(int)($days/12053));
        $days %= 12053;
        $jy += 4*(int)($days/1461);
        $days %= 1461;
        if ($days > 365) {
            $jy += (int)(($days-1)/365);
            $days = ($days-1)%365;
        }
        if ($days < 186) {
            $jm = 1 + (int)($days/31);
            $jd = 1 + ($days%31);
        } else {
            $jm = 7 + (int)(($days-186)/30);
            $jd = 1 + (($days-186)%30);
        }
        return [$jy, $jm, $jd];
    }

    public static function format_jalali($date_str) {
        if (empty($date_str)) return '';
        $d = DateTime::createFromFormat('Y-m-d', $date_str);
        if (!$d) return $date_str;
        list($jy, $jm, $jd) = self::gregorian_to_jalali((int)$d->format('Y'), (int)$d->format('m'), (int)$d->format('d'));
        $months = ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
        return $jd . ' ' . ($months[$jm] ?? $jm) . ' ' . $jy;
    }
}

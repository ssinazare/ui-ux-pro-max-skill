<?php
if (!defined('ABSPATH')) exit;

class Sina_Booking_Elementor {

    public static function init() {
        add_action('elementor/widgets/register', [__CLASS__, 'register_widgets']);
        add_action('elementor/elements/categories_registered', [__CLASS__, 'add_category']);
    }

    public static function add_category($elements_manager) {
        $elements_manager->add_category(
            'sina-booking',
            [
                'title' => 'نوبت دهی سینا',
                'icon' => 'fa fa-plug',
            ]
        );
    }

    public static function register_widgets($widgets_manager) {
        if (!class_exists('Elementor\Widget_Base')) return;

        // Doctors List Widget
        $widgets_manager->register(new Sina_Elementor_Doctors_Widget());
        // Booking Form Widget
        $widgets_manager->register(new Sina_Elementor_Booking_Widget());
        // Tracking Widget
        $widgets_manager->register(new Sina_Elementor_Tracking_Widget());
    }
}

if (class_exists('Elementor\Widget_Base')) {

    class Sina_Elementor_Doctors_Widget extends \Elementor\Widget_Base {
        public function get_name() { return 'sina_doctors_list'; }
        public function get_title() { return 'لیست پزشکان'; }
        public function get_icon() { return 'eicon-person'; }
        public function get_categories() { return ['sina-booking']; }

        protected function register_controls() {
            $this->start_controls_section('content', ['label' => 'تنظیمات']);
            $this->add_control('show_specialty', [
                'label' => 'نمایش تخصص',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]);
            $this->add_control('columns', [
                'label' => 'تعداد ستون',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => ['1' => '1', '2' => '2', '3' => '3'],
                'default' => '3',
            ]);
            $this->end_controls_section();
        }

        protected function render() {
            $settings = $this->get_settings_for_display();
            echo do_shortcode('[sina_doctors_list columns="'.$settings['columns'].'" show_specialty="'.$settings['show_specialty'].'"]');
        }
    }

    class Sina_Elementor_Booking_Widget extends \Elementor\Widget_Base {
        public function get_name() { return 'sina_booking_form'; }
        public function get_title() { return 'فرم رزرو نوبت'; }
        public function get_icon() { return 'eicon-calendar'; }
        public function get_categories() { return ['sina-booking']; }

        protected function register_controls() {
            $this->start_controls_section('content', ['label' => 'تنظیمات']);
            $this->add_control('doctor_id', [
                'label' => 'شناسه پزشک (خالی = همه)',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
            ]);
            $this->end_controls_section();
        }

        protected function render() {
            $settings = $this->get_settings_for_display();
            $doctor_id = $settings['doctor_id'];
            if ($doctor_id) {
                echo do_shortcode('[sina_booking doctor_id="'.$doctor_id.'"]');
            } else {
                echo do_shortcode('[sina_booking]');
            }
        }
    }

    class Sina_Elementor_Tracking_Widget extends \Elementor\Widget_Base {
        public function get_name() { return 'sina_tracking'; }
        public function get_title() { return 'پیگیری نوبت'; }
        public function get_icon() { return 'eicon-search'; }
        public function get_categories() { return ['sina-booking']; }

        protected function register_controls() {
            $this->start_controls_section('content', ['label' => 'تنظیمات']);
            $this->add_control('placeholder', [
                'label' => 'متن نگهدارنده',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'کد رهگیری خود را وارد کنید',
            ]);
            $this->end_controls_section();
        }

        protected function render() {
            $settings = $this->get_settings_for_display();
            echo '<div class="sina-tracking-widget">';
            echo do_shortcode('[sina_tracking placeholder="'.$settings['placeholder'].'"]');
            echo '</div>';
        }
    }
}

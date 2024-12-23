<?php
if (!defined('ABSPATH')) {
    exit;
}

class Dynamic_Surveys_Frontend
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        add_shortcode('dynamic_surveys', array($this, 'dynamic_surveys_shortcode'));
    }

    function frontend_scripts()
    {
        wp_enqueue_script('wp-i18n');
        wp_enqueue_script('wp-escape-html');

        wp_enqueue_style('toastr-css', DYNAMIC_SURVEYS_URL . '/assets/css/toastr.min.css', array(), DYNAMIC_SURVEYS_VERSION);
        wp_enqueue_style('dynamic-surveys-frontend-style', DYNAMIC_SURVEYS_URL . 'assets/css/dynamic-surveys-frontend.css', array(), DYNAMIC_SURVEYS_VERSION);

        wp_enqueue_script('chart-js', DYNAMIC_SURVEYS_URL . 'assets/js/chart.js', array(), DYNAMIC_SURVEYS_VERSION, true);
        wp_enqueue_script('toastr-js', DYNAMIC_SURVEYS_URL . '/assets/js/toastr.min.js', ['jquery'], DYNAMIC_SURVEYS_VERSION, true);
        wp_enqueue_script(
            'dynamic-surveys-frontend-script',
            DYNAMIC_SURVEYS_URL . 'assets/js/dynamic-surveys-frontend.js',
            array('jquery', 'chart-js', 'toastr-js', 'wp-i18n', 'wp-escape-html'),
            DYNAMIC_SURVEYS_VERSION,
            true
        );

        wp_set_script_translations('dynamic-surveys-frontend-script', 'dynamic-surveys');

        wp_localize_script('dynamic-surveys-frontend-script', 'dynamic_surveys_frontend', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dynamic_surveys_frontend_nonce')
        ));
    }

    function dynamic_surveys_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts);

        if (!$atts['id']) {
            return esc_html__('Invalid survey ID', 'dynamic-surveys');
        }

        $survey = Dynamic_Surveys_Manager::get_survey($atts['id']);
        if (!$survey) {
            return esc_html__('Survey not found', 'dynamic-surveys');
        }

        if ($survey->status !== 'open') {
            return sprintf(
                '<div class="dynamic-surveys-message">%s</div>',
                esc_html__('This survey is currently closed', 'dynamic-surveys')
            );
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            $login_url = wp_login_url(get_permalink());
            return sprintf(
                '<div class="dynamic-surveys-message">%s<br><a href="%s" class="dynamic-surveys-login-link">%s</a></div>',
                esc_html__('Please log in to participate in the survey', 'dynamic-surveys'),
                esc_url($login_url),
                esc_html__('Click here to login', 'dynamic-surveys')
            );
        }

        $has_voted = Dynamic_Surveys_Manager::has_user_voted($survey->id, $user_id);

        $results = null;
        if ($has_voted) {
            $results = Dynamic_Surveys_Manager::get_survey_results($survey->id);
        }

        ob_start();
        include DYNAMIC_SURVEYS_PATH . 'templates/frontend/dynamic-surveys-frontend-display.php';
        return ob_get_clean();
    }
}

new Dynamic_Surveys_Frontend();

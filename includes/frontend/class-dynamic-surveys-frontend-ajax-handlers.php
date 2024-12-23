<?php
if (!defined('ABSPATH')) {
    exit;
}

class Dynamic_Surveys_Frontend_Ajax_Handlers {
    public function __construct() {
        add_action('wp_ajax_dynamic_surveys_submit_vote', array($this, 'handle_vote_submission'));
        add_action('wp_ajax_nopriv_dynamic_surveys_submit_vote', array($this, 'handle_vote_submission'));
    }

    public function handle_vote_submission() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dynamic_surveys_frontend_nonce')) {
            wp_send_json_error(array('message' => esc_html__('Security check failed', 'dynamic-surveys')));
            exit;
        }

        // Validate and sanitize inputs
        $survey_id = isset($_POST['survey_id']) ? absint($_POST['survey_id']) : 0;
        $option_id = isset($_POST['option']) ? sanitize_text_field(wp_unslash($_POST['option'])) : '';

        // Basic validation - check if values are empty or invalid
        if (empty($survey_id) || $option_id === '') {
            wp_send_json_error(array('message' => esc_html__('Invalid survey data provided', 'dynamic-surveys')));
            exit;
        }

        global $wpdb;

        // Check if survey exists and is open
        $survey = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dynamic_surveys WHERE id = %d AND status = 'open'",
            $survey_id
        ));

        if (!$survey) {
            wp_send_json_error(array('message' => esc_html__('Survey not found or closed', 'dynamic-surveys')));
            exit;
        }

        // Get user information
        $user_id = get_current_user_id();
        $ip_address = $this->get_client_ip();

        // Check for existing votes from this user/IP
        $existing_vote = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}dynamic_surveys_votes 
            WHERE survey_id = %d AND user_id = %d ",
            $survey_id,
            $user_id
        ));

        if ($existing_vote > 0) {
            wp_send_json_error(array('message' => esc_html__('You have already voted in this survey', 'dynamic-surveys')));
            exit;
        }

        // Validate that option exists in survey options
        $survey_options = json_decode($survey->options, true);
        if (!isset($survey_options[$option_id])) {
            wp_send_json_error(array('message' => esc_html__('Invalid option selected', 'dynamic-surveys')));
            exit;
        }

        // Insert vote
        $result = $wpdb->insert(
            $wpdb->prefix . 'dynamic_surveys_votes',
            array(
                'survey_id' => $survey_id,
                'user_id' => $user_id,
                'option_id' => $option_id,
                'ip_address' => $ip_address,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => esc_html__('Failed to submit vote', 'dynamic-surveys')));
            exit;
        }

        // Invalidate the cache for this survey
        wp_cache_delete('dynamic_surveys_results_' . $survey_id);
        
        // Get updated results
        $results = $this->get_survey_results($survey_id);

        wp_send_json_success(array(
            'message' => esc_html__('Vote submitted successfully', 'dynamic-surveys'),
            'results' => $results
        ));
    }

    private function get_survey_results($survey_id) {
        global $wpdb;
        
        // Get the survey to access options
        $survey = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dynamic_surveys WHERE id = %d",
            $survey_id
        ));
        
        if (!$survey) {
            return array();
        }
        
        // Get all options
        $options = json_decode($survey->options, true);
        
        // Get vote counts
        $votes = $wpdb->get_results($wpdb->prepare(
            "SELECT option_id, COUNT(*) as count 
            FROM {$wpdb->prefix}dynamic_surveys_votes 
            WHERE survey_id = %d 
            GROUP BY option_id",
            $survey_id
        ));
        
        // Format results with option text
        $results = array();
        foreach ($votes as $vote) {
            $option_text = isset($options[$vote->option_id]) ? $options[$vote->option_id] : $vote->option_id;
            $results[] = array(
                'option_id' => $option_text,
                'count' => (int)$vote->count
            );
        }
        
        return $results;
    }

    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key])) {
                $ip = sanitize_text_field(wp_unslash($_SERVER[$key]));
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '127.0.0.1';
    }
}

new Dynamic_Surveys_Frontend_Ajax_Handlers();

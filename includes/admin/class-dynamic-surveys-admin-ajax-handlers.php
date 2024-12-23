    <?php
    if (! defined('ABSPATH')) {
        exit;
    }


    class Dynamic_Surveys_Admin_Ajax_Handlers
    {

        public function __construct()
        {
            add_action('wp_ajax_dynamic_surveys_create_survey', array($this, 'dynamic_surveys_admin_create_survey_handler'));
            add_action('wp_ajax_dynamic_surveys_delete_survey', array($this, 'dynamic_surveys_admin_delete_survey_handler'));
            add_action('wp_ajax_dynamic_surveys_toggle_survey_status', array($this, 'dynamic_surveys_admin_toggle_survey_status_handler'));
            add_action('wp_ajax_dynamic_surveys_export_survey', array($this, 'dynamic_surveys_handle_export_survey'));
        }

        public function dynamic_surveys_admin_create_survey_handler()
        {
            if (! isset($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dynamic_surveys_admin_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Security check failed', 'dynamic-surveys')));
            }


            // Check user capabilities
            if (! current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('Insufficient permissions', 'dynamic-surveys')));
            }

            // Validate required fields
            if (
                ! isset($_POST['title'], $_POST['question'], $_POST['options']) ||
                empty($_POST['title']) || empty($_POST['question']) || empty($_POST['options'])
            ) {
                wp_send_json_error(array('message' => esc_html__('Please fill in all required fields', 'dynamic-surveys')));
            }

            // Sanitize input
            $title    = sanitize_text_field(wp_unslash($_POST['title']));
            $question = sanitize_text_field(wp_unslash($_POST['question']));
            $options  = array_map('sanitize_text_field', wp_unslash($_POST['options']));

            global $wpdb;
            $table_name = $wpdb->prefix . 'dynamic_surveys';

            $result = $wpdb->insert(
                $table_name,
                array(
                    'title'      => $title,
                    'question'   => $question,
                    'options'    => wp_json_encode($options),
                    'status'     => 'open',
                    'created_at' => current_time('mysql'),
                ),
                array('%s', '%s', '%s', '%s', '%s')
            );

            if (false === $result) {
                wp_send_json_error(array('message' => esc_html__('Failed to create survey', 'dynamic-surveys')));
            }

            wp_send_json_success(array(
                'message' => esc_html__('Survey created successfully!', 'dynamic-surveys'),
                'survey'  => array(
                    'id'         => $wpdb->insert_id,
                    'title'      => $title,
                    'question'   => $question,
                    'options'    => $options,
                    'status'     => 'open',
                    'created_at' => current_time('mysql'),
                ),
            ));
        }


        public function dynamic_surveys_admin_delete_survey_handler()
        {
            // Verify nonce
            if (! isset($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dynamic_surveys_admin_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Security check failed', 'dynamic-surveys')));
            }


            // Check user capabilities
            if (! current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('Insufficient permissions', 'dynamic-surveys')));
            }

            if (! isset($_POST['survey_id'])) {
                wp_send_json_error(array('message' => esc_html__('Survey ID is required', 'dynamic-surveys')));
            }

            $survey_id = intval(wp_unslash($_POST['survey_id']));

            global $wpdb;

            // Delete survey
            $result = $wpdb->delete(
                $wpdb->prefix . 'dynamic_surveys',
                array('id' => $survey_id),
                array('%d')
            );

            if (false === $result) {
                wp_send_json_error(array('message' => esc_html__('Failed to delete survey', 'dynamic-surveys')));
            }

            // Also delete related votes
            $wpdb->delete(
                $wpdb->prefix . 'dynamic_surveys_votes',
                array('survey_id' => $survey_id),
                array('%d')
            );

            wp_send_json_success(array('message' => esc_html__('Survey deleted successfully', 'dynamic-surveys')));
        }

        public function dynamic_surveys_admin_toggle_survey_status_handler()
        {
            // Verify nonce
            if (! isset($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dynamic_surveys_admin_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Security check failed', 'dynamic-surveys')));
            }


            // Check user capabilities
            if (! current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('Insufficient permissions', 'dynamic-surveys')));
            }

            if (! isset($_POST['survey_id'], $_POST['current_status'])) {
                wp_send_json_error(array('message' => esc_html__('Required fields missing', 'dynamic-surveys')));
            }

            $survey_id      = intval(wp_unslash($_POST['survey_id']));
            $current_status = sanitize_text_field(wp_unslash($_POST['current_status']));
            $new_status     = ('open' === $current_status) ? 'closed' : 'open';

            global $wpdb;

            $result = $wpdb->update(
                $wpdb->prefix . 'dynamic_surveys',
                array('status' => $new_status),
                array('id' => $survey_id),
                array('%s'),
                array('%d')
            );

            if (false === $result) {
                wp_send_json_error(array('message' => esc_html__('Failed to update survey status', 'dynamic-surveys')));
            }

            wp_send_json_success(array(
                'message'    => esc_html__('Survey status updated successfully', 'dynamic-surveys'),
                'new_status' => $new_status,
            ));
        }

        public function dynamic_surveys_handle_export_survey()
        {
            // Verify nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dynamic_surveys_admin_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Security check failed', 'dynamic-surveys')));
                exit;
            }

            // Verify user capabilities
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('Permission denied', 'dynamic-surveys')));
                exit;
            }

            // Get survey ID
            $survey_id = isset($_POST['survey_id']) ? intval($_POST['survey_id']) : 0;
            if (!$survey_id) {
                wp_send_json_error(array('message' => 'Invalid survey ID'));
                exit;
            }

            global $wpdb;

            // Get survey details
            $survey = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}dynamic_surveys WHERE id = %d",
                $survey_id
            ));

            if (!$survey) {
                wp_send_json_error(array('message' => 'Survey not found'));
                exit;
            }

            // Get all votes for this survey
            $votes = $wpdb->get_results($wpdb->prepare(
                "SELECT v.*, u.display_name 
                    FROM {$wpdb->prefix}dynamic_surveys_votes v 
                    LEFT JOIN {$wpdb->users} u ON v.user_id = u.ID 
                    WHERE v.survey_id = %d 
                    ORDER BY v.created_at DESC",
                $survey_id
            ));

            // Prepare CSV data
            $csv_data = array();
            $csv_data[] = array('User', 'Option Selected', 'IP Address', 'Date');

            foreach ($votes as $vote) {
                $csv_data[] = array(
                    $vote->display_name ?: 'Anonymous',
                    $vote->option_id,
                    $vote->ip_address,
                    $vote->created_at
                );
            }

            // Send JSON response with CSV data
            wp_send_json_success(array(
                'data' => $csv_data,
                'filename' => sanitize_title($survey->title) . '-results.csv'
            ));
        }
    }

    new Dynamic_Surveys_Admin_Ajax_Handlers();

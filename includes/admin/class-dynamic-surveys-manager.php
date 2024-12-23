<?php
if (!defined('ABSPATH')) {
    exit;
}

class Dynamic_Surveys_Manager
{
    public static function create_survey($title, $question, $options)
    {
        global $wpdb;

        $data = array(
            'title' => sanitize_text_field($title),
            'question' => sanitize_text_field($question),
            'options' => wp_json_encode(array_map('sanitize_text_field', $options)),
            'status' => 'open'
        );

        $wpdb->insert("{$wpdb->prefix}dynamic_surveys", $data);
        return $wpdb->insert_id;
    }

    public static function get_survey($id)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dynamic_surveys WHERE id = %d",
            $id
        ));
    }

    public static function get_all_surveys()
    {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dynamic_surveys ORDER BY created_at DESC");
    }

    public static function delete_survey($id)
    {
        global $wpdb;
        $wpdb->delete("{$wpdb->prefix}dynamic_surveys", array('id' => $id));
        $wpdb->delete("{$wpdb->prefix}dynamic_surveys_votes", array('survey_id' => $id));
    }

    public static function update_survey_status($id, $status)
    {
        global $wpdb;
        return $wpdb->update(
            "{$wpdb->prefix}dynamic_surveys",
            array('status' => $status),
            array('id' => $id)
        );
    }

    public static function has_user_voted($survey_id, $user_id)
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}dynamic_surveys_votes WHERE survey_id = %d AND user_id = %d",
            $survey_id,
            $user_id
        )) > 0;
    }

    /**
     * Get survey results with caching
     *
     * @param int $survey_id The survey ID
     * @return array|null The survey results or null if not found
     */
    public static function get_survey_results($survey_id)
    {
        // Try to get cached results
        $cache_key = 'dynamic_surveys_results_' . $survey_id;
        $results = wp_cache_get($cache_key);

        if (false === $results) {
            global $wpdb;

            // Get survey first
            $survey = self::get_survey($survey_id);
            if (!$survey) {
                return null;
            }

            $options = json_decode($survey->options, true);
            $votes_count = array();

            // Get all votes using prepare statement
            $votes = $wpdb->get_results($wpdb->prepare(
                "SELECT option_id, COUNT(*) as count 
                    FROM {$wpdb->prefix}dynamic_surveys_votes 
                    WHERE survey_id = %d 
                    GROUP BY option_id",
                $survey_id
            ));

            // Process votes
            foreach ($options as $index => $option) {
                $votes_count[$index] = 0; // Initialize with 0
                foreach ($votes as $vote) {
                    if ($vote->option_id == $index) {
                        $votes_count[$index] = (int)$vote->count;
                        break;
                    }
                }
            }

            $results = array(
                'type' => 'pie',
                'data' => array(
                    'labels' => $options,
                    'datasets' => array(
                        array(
                            'data' => array_values($votes_count),
                            'backgroundColor' => array(
                                '#FF6384',
                                '#36A2EB',
                                '#FFCE56',
                                '#4BC0C0',
                                '#9966FF',
                                '#FF9F40'
                            )
                        )
                    )
                ),
                'options' => array(
                    'responsive' => true
                )
            );

            // Cache the results for 5 minutes
            wp_cache_set($cache_key, $results, '', 300);
        }

        return $results;
    }
}

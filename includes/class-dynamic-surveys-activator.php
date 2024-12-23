<?php
if (!defined('ABSPATH')) {
    exit;
}

class Dynamic_Surveys_Activator
{


	public static function activate()
	{

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Create surveys table
		$sql_surveys = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dynamic_surveys (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        question text NOT NULL,
        options longtext NOT NULL,
        status varchar(20) DEFAULT 'open',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

		// Create votes table
		$sql_votes = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dynamic_surveys_votes (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        survey_id mediumint(9) NOT NULL,
        user_id bigint(20) NOT NULL,
        option_id varchar(32) NOT NULL,
        ip_address varchar(45),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY survey_user (survey_id, user_id),
        KEY survey_ip (survey_id, ip_address)
    ) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql_surveys);
		dbDelta($sql_votes);


	}

}

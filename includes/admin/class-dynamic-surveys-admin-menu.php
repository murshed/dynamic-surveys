<?php
if (!defined('ABSPATH')) {
    exit;
}
class Dynamic_Surveys_Admin_Menu
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    function add_admin_menu()
    {
        $hook = add_submenu_page(
            'tools.php',
            'Dynamic Surveys',
            'Dynamic Surveys',
            'manage_options',
            'dynamic-surveys',
            array($this, 'dynamic_surveys_admin_page')
        );

        add_action("admin_print_scripts-{$hook}", array($this, 'dynamic_surveys_enqueue_admin_scripts'));
    }

    function dynamic_surveys_admin_page()
    {
        include DYNAMIC_SURVEYS_PATH . 'templates/admin/dynamic-surveys-admin-display.php';
    }

    function dynamic_surveys_enqueue_admin_scripts()
    {
        // Enqueue WordPress scripts
        wp_enqueue_script('wp-i18n');
        wp_enqueue_script('wp-escape-html');

        wp_enqueue_style('toastr-admin-css', DYNAMIC_SURVEYS_URL . '/assets/css/toastr.min.css', array(), DYNAMIC_SURVEYS_VERSION);
        wp_enqueue_style('dynamic-surveys-admin-css', DYNAMIC_SURVEYS_URL . '/assets/css/dynamic-surveys-admin.css', array(), DYNAMIC_SURVEYS_VERSION);

        wp_enqueue_script('jquery');
        wp_enqueue_script('toastr-admin-js', DYNAMIC_SURVEYS_URL . '/assets/js/toastr.min.js', ['jquery'], DYNAMIC_SURVEYS_VERSION, true);
        wp_enqueue_script('dynamic-surveys-admin-js', DYNAMIC_SURVEYS_URL . '/assets/js/dynamic-surveys-admin.js', ['jquery', 'toastr-admin-js', 'wp-i18n', 'wp-escape-html', 'wp-util'], DYNAMIC_SURVEYS_VERSION, true);

        wp_set_script_translations('dynamic-surveys-admin-js', 'dynamic-surveys');

        wp_localize_script('dynamic-surveys-admin-js', 'dynamic_surveys_admin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dynamic_surveys_admin_nonce')
        ]);
    }
}

new Dynamic_Surveys_Admin_Menu();

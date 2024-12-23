=== Dynamic Surveys ===
Contributors: marufmks  
Tags: surveys, forms, feedback, polls, voting  
Stable tag: 1.0.1  
Tested up to: 6.7  
Requires at least: 5.8  
Requires PHP: 7.4  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

Create and manage simple surveys with real-time results display using beautiful pie charts.

== Description ==

Dynamic Surveys is a lightweight yet powerful WordPress plugin that enables site administrators to create and manage surveys effortlessly. Users can participate in surveys, and results are displayed in real time using responsive pie charts powered by Chart.js.

### Features

- Easy survey creation with customizable options  
- Real-time results display with beautiful pie charts  
- Shortcode support for embedding surveys anywhere  
- User-based voting system to prevent duplicate votes  
- Survey status management (open/closed)  
- Mobile-responsive design  
- One-click shortcode copying for easy implementation  
- Toast notifications for better user experience  
- Translation-ready for multilingual sites  
- Export survey results to CSV format  

### Usage Instructions

1. Navigate to **Tools > Dynamic Surveys** in the WordPress admin panel.  
2. Create surveys with multiple-choice options.  
3. Copy the generated shortcode for your survey.  
4. Paste the shortcode into any post or page.  
5. Users can vote and view results instantly.

**Example Shortcode**:  
`[dynamic_surveys id="1"]`  
Replace `1` with your actual survey ID.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/dynamic-surveys/` directory, or install the plugin via the WordPress Plugins screen.  
2. Activate the plugin through the "Plugins" screen in WordPress.  
3. Go to **Tools > Dynamic Surveys** to create and manage your surveys.  
4. Use the `[dynamic_surveys id="X"]` shortcode to display surveys in your posts or pages.

== Frequently Asked Questions ==

### Can users vote multiple times?  
No, the plugin tracks votes by user ID and prevents duplicate voting to ensure accuracy.

### Do I need coding skills to use this plugin?  
No coding knowledge is required. The plugin provides an intuitive interface to create and manage surveys.

### Can I customize the appearance of surveys?  
Yes, you can add custom CSS to match your site's theme.

### Is the plugin translation-ready?  
Yes, the plugin is translation-ready and supports localization.

== Screenshots ==

1. **Backend Dashboard View** - Survey creation and management interface in the WordPress admin panel.  
2. **Frontend View** - A live survey displayed on a WordPress page or post.

== Changelog ==

### 1.0.1  
* Fixed prefix naming issues to ensure compatibility.  
* Removed redundant "Requires" headers from `readme.txt`.  
* Fixed PHP whitespace issues.  
* Enhanced code quality based on WordPress Plugin Review feedback.  

### 1.0.0  
* Initial release.  
* Basic survey creation and management.  
* Real-time results display using pie charts.  
* User vote tracking.  
* Shortcode support for survey embedding.  
* Mobile-responsive design.  
* CSV export functionality.  

== Upgrade Notice ==

### 1.0.1  
This update resolves compatibility issues and includes important fixes to meet WordPress Plugin Directory requirements.

== Privacy Policy ==

Dynamic Surveys plugin stores the following data:  
- Survey questions and options.  
- User votes (user ID and selected option).  
- IP addresses of voters.  

This data is stored in your WordPress database and is not shared with any third parties.

== Credits ==

Dynamic Surveys utilizes the following open-source libraries:  
- **Chart.js** - [MIT License](https://opensource.org/licenses/MIT)  
- **Toastr.js** - [MIT License](https://opensource.org/licenses/MIT)  

== Support ==

For support, please visit the [Plugin Support Forum](https://wordpress.org/support/plugin/dynamic-surveys/).

== Contribute ==

Contributions are welcome! You can contribute to the development of this plugin on GitHub:  
[Dynamic Surveys on GitHub](https://github.com/marufmks/dynamic-surveys)

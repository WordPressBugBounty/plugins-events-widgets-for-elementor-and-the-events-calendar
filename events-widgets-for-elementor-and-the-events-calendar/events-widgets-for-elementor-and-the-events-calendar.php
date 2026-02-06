<?php
/**
 * Plugin Name: Events Widgets For Elementor And The Events Calendar
 * Description: <a href="http://wordpress.org/plugins/the-events-calendar/">📅 The Events Calendar Addon</a> - Events Widget to show The Events Calendar plugin events list easily inside Elementor page builder pages.
 * Plugin URI:  https://eventscalendaraddons.com/plugin/events-widgets-pro/?utm_source=ectbe_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=plugin_uri
 * Version:     1.6.28
 * Author:      Cool Plugins
 * Author URI:  https://coolplugins.net/?utm_source=ectbe_plugin&utm_medium=inside&utm_campaign=author_page&utm_content=plugins_list
 * Text Domain: ectbe
 * Elementor tested up to: 3.33.4
 * Elementor Pro tested up to: 3.33.2
 * Requires Plugins: elementor, the-events-calendar

 */
if (!defined('ABSPATH')) {
    exit;
}
if (defined('ECTBE_VERSION')) {
    return;
}
define('ECTBE_VERSION', '1.6.28');
define('ECTBE_FILE', __FILE__);
define('ECTBE_PATH', plugin_dir_path(ECTBE_FILE));
define('ECTBE_URL', plugin_dir_url(ECTBE_FILE));
define('ECTBE_FEEDBACK_API', 'https://feedback.coolplugins.net/');

register_activation_hook(ECTBE_FILE, array('Events_Calendar_Addon', 'ectbe_activate'));
register_deactivation_hook(ECTBE_FILE, array('Events_Calendar_Addon', 'ectbe_deactivate'));
/**
 * Class Events_Calendar_Addon
 */
final class Events_Calendar_Addon
{
    /**
     * Plugin instance.
     *
     * @var Events_Calendar_Addon
     * @access private
     */
    private static $instance = null;
    /**
     * Get plugin instance.
     *
     * @return Events_Calendar_Addon
     * @static
     */
    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    /**
     * Constructor.
     *
     * @access private
     */
    private function __construct()
    {
        $this->include_files();
        // Load the plugin after Elementor (and other plugins) are loaded.
        add_action('init', array($this, 'ectbe_add_text_domain'));
        add_action('plugins_loaded', array($this, 'ectbe_plugins_loaded'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'ectbe_add_action_links'));
        add_action('plugin_row_meta', array($this, 'ectbe_addMeta_Links'), 10, 2);
        add_filter('tribe_rest_event_max_per_page', function($max) {
            return 999;
        });
        add_filter('rest_tribe_events_collection_params', function($params) {
            if (isset($params['per_page'])) {
                $params['per_page']['maximum'] = 999;
            }
            return $params;
        });
    }

    /**
    * Initialize cron : MUST USE ON PLUGIN ACTIVATION
    */
    public static function ectbe_cron_job_init() {
        $review_option = get_option("cpfm_opt_in_choice_cool_events");

        if ($review_option === 'yes') {
            if (!wp_next_scheduled('ectbe_extra_data_update')) {

                wp_schedule_event(time(), 'every_30_days', 'ectbe_extra_data_update');

            }
        }
    }

    public function include_files()
    {
        require_once __DIR__ . '/admin/events-addon-page/events-addon-page.php';
        cool_plugins_events_addon_settings_page('the-events-calendar', 'cool-plugins-events-addon', '📅 Events Addons For The Events Calendar');

        require_once ECTBE_PATH . 'admin/cpfm-feedback/cron/class-cron.php';
    }
    /**
     * Add meta links to the Plugins list page.
     *
     * @param array  $links The current action links.
     * @param string $file  The plugin to see if we are on Event Single Page.
     *
     * @return array The modified action links array.
     */
    public function ectbe_addMeta_Links($links, $file)
    {
        if (strpos($file, basename(__FILE__))) {
            $ectanchor = esc_html__('Video Tutorials', 'ectbe');
            $ectvideourl = 'https://youtube.com/playlist?list=PLAs6S1hKb-gNX_A-ZpsD-tO9aQdMmwrU5&si=m9mE0xDu8Ei0x41u';
            $links[] = '<a href="' . esc_url($ectvideourl) . '" target="_blank">' . $ectanchor . '</a>';
        }

        return $links;
    }

    // custom links for add widgets in all plugins section
    public function ectbe_add_action_links($links)
    {
        $plugin_visit_website = 'https://eventscalendaraddons.com/plugin/events-widgets-pro/?utm_source=ectbe_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=plugins_list';
        $links[] = '<a  style="font-weight:bold" href="' . esc_url($plugin_visit_website) . '" target="_blank">' . esc_html__('Get Pro', 'ectbe') . '</a>';
        return $links;
    }

    public function ectbe_add_text_domain()
    {
        load_plugin_textdomain('ectbe', false, basename(dirname(__FILE__)) . '/languages/');

        if (!get_option( 'ectbe_initial_save_version' ) ) {
            add_option( 'ectbe_initial_save_version', ECTBE_VERSION );
        }

        if(!get_option( 'ectbe-install-date' ) ) {
            add_option( 'ectbe-install-date', gmdate('Y-m-d h:i:s') );
        }
    }

    /**
     * Code you want to run when all other plugins loaded.
     */
    public function ectbe_plugins_loaded()
    {
        // Require the main plugin file
        require __DIR__ . '/includes/functions.php';
        require __DIR__ . '/includes/class-ectbe.php';
        if (is_admin()) {
            add_action('admin_init', array($this, 'ectbe_show_upgrade_notice'));
            require __DIR__ . '/admin/feedback-notice/class-admin-notice.php';
            require_once __DIR__ . '/admin/feedback/admin-feedback-form.php';
        }

        if(!class_exists('CPFM_Feedback_Notice')){
            require_once ECTBE_PATH . 'admin/cpfm-feedback/cpfm-feedback-notice.php';
        }

        add_action('cpfm_register_notice', function () {
        
            if (!class_exists('CPFM_Feedback_Notice') || !current_user_can('manage_options')) {
                return;
            }
            $notice = [
                'title' => __('Events Addons By Cool Plugins', 'ectbe'),
                'message' => __('Help us make this plugin more compatible with your site by sharing non-sensitive site data.', 'ectbe'),
                'pages' => ['cool-plugins-events-addon'],
                'always_show_on' => ['cool-plugins-events-addon'], // This enables auto-show
                'plugin_name'=>'ectbe',
                
            ];

            \CPFM_Feedback_Notice::cpfm_register_notice('cool_events', $notice);

                if (!isset($GLOBALS['cool_plugins_feedback'])) {
                    $GLOBALS['cool_plugins_feedback'] = [];
                }
            
                $GLOBALS['cool_plugins_feedback']['cool_events'][] = $notice;
       
        });
        add_action('cpfm_after_opt_in_ectbe', function($category) {

            if ($category === 'cool_events') {
                \ECTBE_cronjob::ectbe_send_data();
            }
        });
    } // end of ctla_loaded()
    public function ectbe_show_upgrade_notice()
    {

        $installed_version = get_option('ectbe-v');
        if (version_compare($installed_version, '1.6', '<')) {
            ectbe_create_admin_notice(
                array(
                    'id' => 'ectbe-major-update-notice',
                    'message' => '<strong>' . esc_html__('Major Update Notice!', 'ectbe') . '</strong> ' . esc_html__('Please update your events widget settings if you face any style issue after an update of', 'ectbe') . ' <strong>' . esc_html__('Events Widgets For Elementor And The Events Calendar', 'ectbe') . '</strong>.',
                    'review_interval' => 0,
                )
            );
        }

        /*** Plugin review notice file */
        ectbe_create_admin_notice(
            array(
                'id' => 'ectbe-review-box', // required and must be unique
                'slug' => 'ectbe', // required in case of review box
                'review' => true, // required and set to be true for review box
                'review_url' => esc_url('https://wordpress.org/support/plugin/events-widgets-for-elementor-and-the-events-calendar/reviews/?filter=5#new-post'), // required
                'plugin_name' => esc_html__('Events Widgets For Elementor And The Events Calendar', 'ectbe'), // required
                'review_interval' => 3, // optional: this will display review notice
                // after 5 days from the installation_time
                // default is 3
            )
        );
    }
    /**
     * Run when activate plugin.
     */
    public static function ectbe_activate()
    {
        update_option('ectbe-v', ECTBE_VERSION);
        update_option('ectbe-type', 'FREE');
        update_option('ectbe-installDate', gmdate('Y-m-d h:i:s'));

        Events_Calendar_Addon::ectbe_cron_job_init();

            if (!get_option( 'ectbe_initial_save_version' ) ) {
                add_option( 'ectbe_initial_save_version', ECTBE_VERSION );
            }

            if(!get_option( 'ectbe-install-date' ) ) {
                add_option( 'ectbe-install-date', gmdate('Y-m-d h:i:s') );
            }
    }
    /**
     * Run when deactivate plugin.
     */
    public static function ectbe_deactivate()
    {
        if (wp_next_scheduled('ectbe_extra_data_update')) {
            wp_clear_scheduled_hook('ectbe_extra_data_update');
        }
    }
}
function Events_Calendar_Addon()
{
    return Events_Calendar_Addon::get_instance();
}
Events_Calendar_Addon();
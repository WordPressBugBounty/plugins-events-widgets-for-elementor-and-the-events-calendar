<?php
/**
 * Plugin Name: Events Widgets For Elementor And The Events Calendar
 * Description: <a href="http://wordpress.org/plugins/the-events-calendar/">📅 The Events Calendar Addon</a> - Events Widget to show The Events Calendar plugin events list easily inside Elementor page builder pages.
 * Plugin URI:  https://eventscalendaraddons.com/plugin/events-widgets-pro/?utm_source=ectbe_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=plugin_uri
 * Version:     1.9.1
 * Author:      Cool Plugins
 * Author URI:  https://coolplugins.net/?utm_source=ectbe_plugin&utm_medium=inside&utm_campaign=author_page&utm_content=plugins_list
 * Text Domain: events-widgets-for-elementor-and-the-events-calendar
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Elementor tested up to: 4.1.5
 * Elementor Pro tested up to: 4.1.3
 * Requires Plugins: elementor, the-events-calendar

 */
if (!defined('ABSPATH')) {
    exit;
}
if (defined('ECTBE_VERSION')) {
    return;
}
define('ECTBE_VERSION', '1.9.1');
define('ECTBE_FILE', __FILE__);
define('ECTBE_PATH', plugin_dir_path(ECTBE_FILE));
define('ECTBE_URL', plugin_dir_url(ECTBE_FILE));
define('ECTBE_FEEDBACK_API', 'https://feedback.coolplugins.net/');

register_activation_hook(ECTBE_FILE, array('Events_Calendar_Addon', 'ectbe_activate'));
register_deactivation_hook(ECTBE_FILE, array('Events_Calendar_Addon', 'ectbe_deactivate'));
/**
 * Class Events_Calendar_Addon
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
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
        $this->cpfm_feedback_cron_init();
        $this->include_files();
        add_action('plugins_loaded', array($this, 'ectbe_plugins_loaded'));
        add_action('init', array($this, 'register_cpfm_notices'), 999);
        add_action('cpfm_after_opt_in_ectbe', array($this, 'ectbe_handle_cpfm_opt_in'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'ectbe_add_action_links'));
        add_action('plugin_row_meta', array($this, 'ectbe_add_meta_links'), 10, 2);
        add_filter('tribe_rest_event_max_per_page', function($max) {
            return 999;
        });
        add_filter('rest_tribe_events_collection_params', function($params) {
            if (isset($params['per_page'])) {
                $params['per_page']['maximum'] = 999;
            }
            return $params;
        });
        add_action('admin_print_scripts', [$this, 'ect_hide_unrelated_notices']);

        add_action( 'plugin_opt_in_events-widgets-for-elementor-and-the-events-calendar', function () {
			update_option( 'ectbe_has_usage_tracking_consent', true, false );
            $this->ectbe_register_cpfm_usage_cron();
            if ( class_exists( '\CPFM_Usage_Cron' ) ) {
                \CPFM_Usage_Cron::cpfm_schedule_event( 'ectbe_extra_data_update' );
            }
        } );
    }
    public function ect_hide_unrelated_notices(){ 
			
        // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded, Generic.Metrics.NestingLevel.MaxExceeded
        $events_pages = false;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['page'])) {
            
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $page_param = sanitize_key(wp_unslash( $_GET['page'] ));

            $allowed_pages = array_merge(
                array(
                    'cool-plugins-events-addon',
                    'tribe-events-shortcode-template-settings',
                    'tribe_events-events-template-settings',
                    'countdown_for_the_events_calendar',
                    'esas-speaker-sponsor-settings',
                    'esas_speaker',
                    'esas_sponsor',
                    'ewpe',
                    'epta',
                ),
                ECTBE_ECA_Integration::admin_page_slugs()
            );

            if (in_array($page_param, $allowed_pages, true)) {
                $events_pages = true;
            }
        }
        $is_post_type_page = false;

        $current_screen = get_current_screen();
        
        if ( $current_screen && ! empty( $current_screen->post_type ) ) {
        
            $allowed_post_types = array(
                'esas_speaker',
				'esas_sponsor',
				'epta',
				'ewpe'
            );
        
            if ( in_array( $current_screen->post_type, $allowed_post_types, true ) ) {
                $is_post_type_page = true;
            }
        }
        if ($events_pages) {
            global $wp_filter;
            // Define rules to remove callbacks.
            $rules = [
                'user_admin_notices' => [], // remove all callbacks.
                'admin_notices'      => [],
                'all_admin_notices'  => [],
                'admin_footer'       => [
                    'render_delayed_admin_notices', // remove this particular callback.
                ],
            ];
            $notice_types = array_keys($rules);
            foreach ($notice_types as $notice_type) {
                if (empty($wp_filter[$notice_type]) || empty($wp_filter[$notice_type]->callbacks) || ! is_array($wp_filter[$notice_type]->callbacks)) {
                    continue;
                }
                $remove_all_filters = empty($rules[$notice_type]);
                foreach ($wp_filter[$notice_type]->callbacks as $priority => $hooks) {
                    foreach ($hooks as $name => $arr) {
                        if (is_object($arr['function']) && is_callable($arr['function'])) {
                            if ($remove_all_filters) {
                                unset($wp_filter[$notice_type]->callbacks[$priority][$name]);
                            }
                            continue;
                        }
                        $class = ! empty($arr['function'][0]) && is_object($arr['function'][0]) ? strtolower(get_class($arr['function'][0])) : '';
                        // Remove all callbacks except WPForms notices.
                        if ($remove_all_filters && strpos($class, 'wpforms') === false) {
                            unset($wp_filter[$notice_type]->callbacks[$priority][$name]);
                            continue;
                        }
                        $cb = is_array($arr['function']) ? $arr['function'][1] : $arr['function'];
                        // Remove a specific callback.
                        if (! $remove_all_filters) {
                            if (in_array($cb, $rules[$notice_type], true)) {
                                unset($wp_filter[$notice_type]->callbacks[$priority][$name]);
                            }
                            continue;
                        }
                    }
                }
            }
        }
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
        if (!$events_pages && !$is_post_type_page) {

            // ✅ GLOBAL LOCK SYSTEM
            if (!defined('ECT_ADMIN_NOTICE_HOOKED')) {

                define('ECT_ADMIN_NOTICE_HOOKED', true);

                add_action(
                    'admin_notices',
                    array($this, 'ect_dash_admin_notices'),
                    PHP_INT_MAX
                );
            }
        }
    }

    public function ect_dash_admin_notices() {

        // ✅ Double render protection
        if (defined('ECT_ADMIN_NOTICE_RENDERED')) {
            return;
        }

        define('ECT_ADMIN_NOTICE_RENDERED', true);

        do_action('ect_display_admin_notices');
    }

    /**
     * Boot CPFM loader, usage cron registration, and onboarding telemetry filter.
     * Loaded unconditionally so WP-Cron requests can execute data sharing without is_admin().
     *
     * @return void
     */
    public function cpfm_feedback_cron_init() {
        if ( ! class_exists( 'CPFM_Loader' ) ) {
            $file = ECTBE_PATH . 'admin/cpfm-feedback/class-cpfm-loader.php';
            if ( file_exists( $file ) ) {
                require_once $file;
            }
        }

        if ( class_exists( 'CPFM_Loader' ) ) {
            CPFM_Loader::load();
        }

        $this->ectbe_register_cpfm_usage_cron();

        if ( $this->ectbe_has_usage_tracking_consent() && ! wp_next_scheduled( 'ectbe_extra_data_update' ) ) {
            $this->ectbe_maybe_schedule_tracking_cron();
        }

        if ( ! class_exists( 'ECTBE_Onboarding_Cpfm_Data', false ) ) {
            $onboarding_file = ECTBE_PATH . 'admin/ectbe-onboarding/includes/class-ectbe-onboarding-cpfm-data.php';
            if ( file_exists( $onboarding_file ) ) {
                require_once $onboarding_file;
            }
        }

        
    }

    /**
     * Register the shared usage-data cron (admin + WP-Cron).
     *
     * @return void
     */
    public function ectbe_register_cpfm_usage_cron() {
        static $usage_cron_registered = false;

        if ( $usage_cron_registered || ! class_exists( 'CPFM_Usage_Cron' ) ) {
            return;
        }

        $usage_cron_registered = true;

        CPFM_Usage_Cron::cpfm_register(
            array(
                'id'                     => 'ectbe',
                'plugin_name'            => 'Events Widgets For Elementor And The Events Calendar',
                'version'                => ECTBE_VERSION,
                'api'                    => ECTBE_FEEDBACK_API,
                'cron_hook'              => 'ectbe_extra_data_update',
                'consent_master_option'  => 'cpfm_opt_in_choice_cool_events',
                'consent_callback'       => array( $this, 'ectbe_has_usage_tracking_consent' ),
                'install_date_option'    => 'ectbe-install-date',
                'initial_version_option' => 'ectbe_initial_save_version',
                'site_key'               => '24',
                'onboarding_data' => 'cpfm_onboarding_preferences_cool_events',
            )
        );
    }

    /**
     * @return bool
     */
    public function ectbe_has_usage_tracking_consent() {
        return ( 'yes' === get_option( 'cpfm_opt_in_choice_cool_events' ) );
    }

    /**
     * @return void
     */
    public function ectbe_maybe_schedule_tracking_cron() {
        $this->ectbe_register_cpfm_usage_cron();

        if ( class_exists( 'CPFM_Usage_Cron' ) ) {
            CPFM_Usage_Cron::cpfm_schedule_event( 'ectbe_extra_data_update' );
        }
    }

    /**
     * Opt-in handler: send first payload and schedule cron.
     *
     * @param string $category Notice category key.
     * @return void
     */
    public function ectbe_handle_cpfm_opt_in( $category ) {
        if ( 'cool_events' !== $category ) {
            return;
        }

        $this->ectbe_register_cpfm_usage_cron();
        do_action( 'ectbe_extra_data_update' );
        $this->ectbe_maybe_schedule_tracking_cron();
    }

    /**
     * Register CPFM consent, review, and deactivation notices.
     *
     * @return void
     */
    public function register_cpfm_notices() {
        if ( ! is_admin() ) {
            return;
        }

        static $registered = false;
        if ( $registered ) {
            return;
        }
        $registered = true;

        $pages = class_exists( 'ECTBE_ECA_Integration' )
            ? ECTBE_ECA_Integration::admin_page_slugs()
            : array( 'cool-plugins-events-addon', 'ectbe-onboarding' );

        add_action(
            'cpfm_register_notice',
            function () use ( $pages ) {
                if ( ! class_exists( 'CPFM_Feedback_Notice' ) || ! current_user_can( 'manage_options' ) ) {
                    return;
                }

                $notice = array(
                    'title'          => __( 'Events Addons By Cool Plugins', 'events-widgets-for-elementor-and-the-events-calendar' ),
                    'message'        => __( 'Help us make this plugin more compatible with your site by sharing non-sensitive site data.', 'events-widgets-for-elementor-and-the-events-calendar' ),
                    'pages'          => $pages,
                    'always_show_on' => $pages,
                    'plugin_name'    => 'ectbe',
                );

                CPFM_Feedback_Notice::cpfm_register_notice( 'cool_events', $notice );

                if ( ! isset( $GLOBALS['cool_plugins_feedback'] ) ) {
                    $GLOBALS['cool_plugins_feedback'] = array(); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                }

                $GLOBALS['cool_plugins_feedback']['cool_events'][] = $notice; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
            }
        );

        if ( class_exists( 'CPFM_Deactivation_Feedback' ) ) {
            CPFM_Deactivation_Feedback::cpfm_register(
                array(
                    'id'                     => 'ectbe',
                    'slug'                   => 'events-widgets-for-elementor-and-the-events-calendar',
                    'plugin_name'            => 'Events Widgets For Elementor And The Events Calendar',
                    'version'                => defined( 'ECTBE_VERSION' ) ? ECTBE_VERSION : '',
                    'api'                    => defined( 'ECTBE_FEEDBACK_API' ) ? ECTBE_FEEDBACK_API : 'https://feedback.coolplugins.net/',
                    'site_key'               => '24',
                    'install_date_option'    => 'ectbe-install-date',
                    'initial_version_option' => 'ectbe_initial_save_version',
                    'onboarding_data' => 'cpfm_onboarding_preferences_cool_events',
                    'reasons'                => array(
                        'not_working'  => array(
                            'title'       => __( "The plugin isn't working", 'events-widgets-for-elementor-and-the-events-calendar' ),
                            'placeholder' => __( 'Which problem did you run into? We read every reply.', 'events-widgets-for-elementor-and-the-events-calendar' ),
                        ),
                        'not_expected' => array(
                            'title'       => __( "It didn't do what I expected", 'events-widgets-for-elementor-and-the-events-calendar' ),
                            'placeholder' => __( 'What were you hoping it would do?', 'events-widgets-for-elementor-and-the-events-calendar' ),
                        ),
                        'found_better' => array(
                            'title'       => __( 'I found a better plugin', 'events-widgets-for-elementor-and-the-events-calendar' ),
                            'placeholder' => __( 'Mind sharing which one?', 'events-widgets-for-elementor-and-the-events-calendar' ),
                        ),
                        'temporary'    => array(
                            'title'       => __( "It's a temporary deactivation", 'events-widgets-for-elementor-and-the-events-calendar' ),
                            'placeholder' => '',
                        ),
                        'other'        => array(
                            'title'       => __( 'Another reason', 'events-widgets-for-elementor-and-the-events-calendar' ),
                            'placeholder' => __( 'Please tell us more', 'events-widgets-for-elementor-and-the-events-calendar' ),
                        ),
                    ),
                    'i18n'                   => array(
                        'title'        => __( 'Before you go…', 'events-widgets-for-elementor-and-the-events-calendar' ),
                        /* translators: %s: plugin name (bold). */
                        'intro'        => __( 'What made you deactivate %s? Your answer helps us fix it.', 'events-widgets-for-elementor-and-the-events-calendar' ),
                        'submit'       => __( 'Submit & Deactivate', 'events-widgets-for-elementor-and-the-events-calendar' ),
                        'skip'         => __( 'Skip & Deactivate', 'events-widgets-for-elementor-and-the-events-calendar' ),
                        'deactivating' => __( 'Deactivating…', 'events-widgets-for-elementor-and-the-events-calendar' ),
                        'pick_reason'  => __( 'Please choose a reason.', 'events-widgets-for-elementor-and-the-events-calendar' ),
                        'close_label'  => __( 'Close', 'events-widgets-for-elementor-and-the-events-calendar' ),
                        /* translators: %s: company name. */
                        'byline'       => __( 'A plugin by %s', 'events-widgets-for-elementor-and-the-events-calendar' ),
                        'consent'      => __( 'Submitting shares your reason plus your site URL, admin email and basic environment details (PHP, WordPress, active plugins). Skip & Deactivate sends nothing.', 'events-widgets-for-elementor-and-the-events-calendar' ),
                    ),
                )
            );
        }

        if ( ! class_exists( 'CPFM_Review' ) ) {
            $review = ECTBE_PATH . 'admin/cpfm-feedback/class-cpfm-review.php';
            if ( file_exists( $review ) ) {
                require_once $review;
            }
        }

        if ( ! class_exists( 'CPFM_Review' ) ) {
            return;
        }

        CPFM_Review::cpfm_register(
            array(
                'id'          => 'ectbe',
                'plugin_file' => ECTBE_FILE,
                'plugin_name' => 'Events Widgets For Elementor And The Events Calendar',
                'review_url'  => 'https://wordpress.org/support/plugin/events-widgets-for-elementor-and-the-events-calendar/reviews/#new-post',
                'capability'  => 'activate_plugins',
                'quiet_days'  => 0,
                'own_screens' => array(
                    'toplevel_page_cool-plugins-events-addon',
                    'events-addons_page_ectbe-onboarding',
                    'cool-plugins-events-addon_page_ectbe-onboarding',
                ),
                'trigger'     => array(
                    'type'  => 'install_age',
                    'hours' => 24,
                ),
                'notice'      => array(
                    'enabled'        => true,
                    'template'       => 'two_step',
                    'screens'        => array(
                        'plugins',
                        'toplevel_page_cool-plugins-events-addon',
                        'events-addons_page_ectbe-onboarding',
                        'cool-plugins-events-addon_page_ectbe-onboarding',
                    ),
                    'inline_screens' => array(),
                ),
                'row'         => array( 'enabled' => true ),
                'legacy'      => array(
                    'done_options'  => array(
                        'ectbe-ratingDiv' => array( 'yes', 'done', 'dismissed' ),
                    ),
                    'install_dates' => array( 'ectbe-installDate', 'ectbe-install-date' ),
                ),
				'i18n'        => array(
					'like_question' => sprintf(
						/* translators: %s: plugin name. */
						__( 'Do you like the %s plugin?', 'events-widgets-for-elementor-and-the-events-calendar' ),
						'Events Widgets For Elementor And The Events Calendar'
					),
					'yes_button'    => __( 'Yes, I like it', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'dismiss_link'  => __( 'Not good, dismiss', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'later_link'    => __( 'Ask me later', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'thanks_line'   => __( 'That is great to hear! A quick review on WordPress.org would really help us.', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'submit_button' => __( 'Submit review', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'no_link'       => __( 'I do not like it, dismiss', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'row_question'  => __( 'Do you like this plugin?', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'inline_title'  => sprintf(
						/* translators: %s: plugin name. */
						__( 'Enjoying %s?', 'events-widgets-for-elementor-and-the-events-calendar' ),
						'Events Widgets For Elementor And The Events Calendar'
					),
					'inline_text'   => __( 'A short review helps other event organisers find it.', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'close_label'   => __( 'Close', 'events-widgets-for-elementor-and-the-events-calendar' ),
				),
            )
        );
    }

    public function include_files()
    {
        if ( is_admin() ) {
            require_once ECTBE_PATH . 'admin/class-ectbe-eca-integration.php';
            ECTBE_ECA_Integration::boot_admin();
        }
    }
    /**
     * Add meta links to the Plugins list page.
     *
     * @param array  $links The current action links.
     * @param string $file  The plugin to see if we are on Event Single Page.
     *
     * @return array The modified action links array.
     */
    public function ectbe_add_meta_links($links, $file)
    {
        if ( strpos( $file, basename( __FILE__ ) ) !== false ) {
            $ectanchor = esc_html__('Video Tutorials', 'events-widgets-for-elementor-and-the-events-calendar');
            $ectvideourl = 'https://youtube.com/playlist?list=PLAs6S1hKb-gNX_A-ZpsD-tO9aQdMmwrU5&si=m9mE0xDu8Ei0x41u';
            $links[] = '<a href="' . esc_url($ectvideourl) . '" target="_blank" rel="noopener noreferrer" >' . $ectanchor . '</a>';
        }

        return $links;
    }

    // custom links for add widgets in all plugins section
    public function ectbe_add_action_links($links)
    {
        $plugin_visit_website = 'https://eventscalendaraddons.com/plugin/events-widgets-pro/?utm_source=ectbe_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=plugins_list';
        $links[] = '<a  style="font-weight:bold" href="' . esc_url($plugin_visit_website) . '" target="_blank">' . esc_html__('Get Pro', 'events-widgets-for-elementor-and-the-events-calendar') . '</a>';
        return $links;
    }
    /**
     * Code you want to run when all other plugins loaded.
     */
    public function ectbe_plugins_loaded()
    {
        // Require the main plugin file
        require __DIR__ . '/includes/functions.php';
        require __DIR__ . '/includes/class-ectbe.php';
        
    } // end of ctla_loaded()
    
    /**
     * Run when activate plugin.
     */
    public static function ectbe_activate()
    {
        // Decide the post-activation redirect BEFORE writing options so
        // fresh-install detection sees a pristine state.
        // Fresh install → Getting Started; reactivation → Dashboard.
        require_once ECTBE_PATH . 'admin/ectbe-onboarding/class-ectbe-onboarding-page.php';
        if ( class_exists( 'ECTBE_Onboarding_Page' ) ) {
            ECTBE_Onboarding_Page::maybe_schedule_redirect();
        }

        update_option('ectbe-v', ECTBE_VERSION);
        update_option('ectbe-type', 'FREE');
        update_option('ectbe-installDate', gmdate('Y-m-d h:i:s'));

        Events_Calendar_Addon::get_instance()->ectbe_maybe_schedule_tracking_cron();

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
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function Events_Calendar_Addon()
{
    return Events_Calendar_Addon::get_instance();
}
Events_Calendar_Addon();
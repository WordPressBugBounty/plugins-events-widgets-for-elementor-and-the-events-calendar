<?php
/**
 * Boots the shared ECA dashboard module for Events Widgets for Elementor.
 *
 * @package Events_Widgets_For_Elementor_And_The_Events_Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECTBE_ECA_Integration' ) ) {

	/**
	 * Single entry point for ECA dashboard integration.
	 */
	final class ECTBE_ECA_Integration {

		const DASHBOARD_PAGE_SLUG = 'cool-plugins-events-addon';
		const DASHBOARD_VERSION   = '1.0.0';

		/**
		 * Admin page slugs used by CPFM notices and notice-hiding rules.
		 *
		 * @return string[]
		 */
		public static function admin_page_slugs() {
			return array(
				self::DASHBOARD_PAGE_SLUG,
				ECTBE_Onboarding_Page::PAGE_SLUG,
			);
		}

		/**
		 * Load dashboard classes and register this addon as the widgets host.
		 */
		public static function boot_admin() {
			require_once ECTBE_PATH . 'admin/eca-dashboard/includes/class-eca-dashboard-registry.php';
			require_once ECTBE_PATH . 'admin/ectbe-onboarding/class-ectbe-onboarding-page.php';

			$dashboard_url = admin_url( 'admin.php?page=' . self::DASHBOARD_PAGE_SLUG );

			ECA_Dashboard_Registry::submit( self::DASHBOARD_VERSION, ECTBE_PATH . 'admin/eca-dashboard/' );
			ECA_Dashboard_Registry::register_addon(
				array(
					'slug'          => 'widgets',
					'host_slug'     => 'widgets',
					'text_domain'   => 'events-widgets-for-elementor-and-the-events-calendar',
					'dashboard_url' => ECTBE_URL . 'admin/eca-dashboard/',
					'admin_urls'    => array(
						'widgets'   => $dashboard_url,
						'esb'       => admin_url( 'admin.php?page=tribe_events-events-template-settings' ),
						'ect'       => admin_url( 'admin.php?page=tribe_events-events-template-settings' ),
						'divi'      => $dashboard_url,
						'spb'       => $dashboard_url,
						'speakers'  => admin_url( 'admin.php?page=esas-speaker-sponsor-settings' ),
						'countdown' => admin_url( 'admin.php?page=countdown_for_the_events_calendar' ),
						'shortcode' => admin_url( 'admin.php?page=tribe_events-events-template-settings' ),
					),
					'menu'          => array(
						'slug'     => self::DASHBOARD_PAGE_SLUG,
						// Titles translated in ECA_Dashboard_Page::register_menus() on admin_menu (WP 6.7+).
						'position' => 9,
					),
				)
			);

			add_action( 'plugins_loaded', array( 'ECA_Dashboard_Registry', 'boot' ), 20 );
			ECTBE_Onboarding_Page::init();
			// Activation redirect: fresh → onboarding, reactivation → dashboard
			// (scheduled in activate(), consumed via ECTBE_Onboarding_Page::maybe_redirect).
		}
	}
}

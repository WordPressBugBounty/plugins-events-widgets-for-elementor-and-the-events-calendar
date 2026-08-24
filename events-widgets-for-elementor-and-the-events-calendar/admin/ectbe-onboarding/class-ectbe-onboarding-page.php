<?php
/**
 * ECTBE onboarding wizard admin page.
 *
 * @package Events_Widgets_For_Elementor_And_The_Events_Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECTBE_Onboarding_Page' ) ) {

	/**
	 * Getting Started wizard for Events Widgets for Elementor.
	 */
	final class ECTBE_Onboarding_Page {

		const PAGE_SLUG          = 'ectbe-onboarding';
		const COMPLETED_OPTION   = 'ectbe_onboarding_completed';
		const REDIRECT_TRANSIENT = 'ectbe_onboarding_redirect';

		/**
		 * Editor/builder icon URLs.
		 *
		 * @return array<string, string>
		 */
		public static function editor_icon_urls() {
			return class_exists( 'ECA_Dashboard_Registry' )
				? ECA_Dashboard_Registry::editor_icon_urls()
				: array();
		}

		/**
		 * Register hooks.
		 */
		public static function init() {
			require_once __DIR__ . '/includes/class-ectbe-onboarding-draft-page.php';

			add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 15 );
			add_action( 'admin_init', array( __CLASS__, 'ensure_admin_page_title' ), 1 );
			add_action( 'admin_init', array( __CLASS__, 'maybe_redirect' ) );
			add_filter( 'admin_body_class', array( __CLASS__, 'admin_body_class' ) );
			add_action( 'admin_head', array( __CLASS__, 'print_fullscreen_head' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
			add_action( 'admin_notices', array( __CLASS__, 'suppress_foreign_notices' ), PHP_INT_MIN );
			add_action( 'all_admin_notices', array( __CLASS__, 'suppress_foreign_notices' ), PHP_INT_MIN );
			add_action( 'wp_ajax_ectbe_onboarding_complete', array( __CLASS__, 'ajax_complete' ) );
			add_action( 'wp_ajax_ectbe_onboarding_create_page', array( __CLASS__, 'ajax_create_page' ) );
			add_action( 'wp_ajax_ectbe_onboarding_save_preferences', array( __CLASS__, 'ajax_save_preferences' ) );
			add_action( 'wp_ajax_ectbe_onboarding_activate_pro', array( __CLASS__, 'ajax_activate_pro' ) );
		}

		/**
		 * Schedule a one-shot post-activation redirect.
		 * Call from the activation hook BEFORE writing install options.
		 *
		 * Fresh install  → Getting Started (onboarding).
		 * Reactivation   → shared Events Addons dashboard.
		 *
		 * @return void
		 */
		public static function maybe_schedule_redirect() {
			$is_fresh_install = ( false === get_option( 'ectbe-install-date', false ) )
				&& ( false === get_option( 'ectbe-v', false ) );

			$target = $is_fresh_install ? 'onboarding' : 'dashboard';
			// Short TTL: WP reloads admin immediately after Activate, then maybe_redirect()
			// consumes this. If it expires, the user stays on the dashboard (onboarding
			// remains available from the menu).
			set_transient( self::REDIRECT_TRANSIENT, $target, MINUTE_IN_SECONDS );
		}

		/**
		 * Consume the post-activation redirect transient (one shot).
		 *
		 * @return void
		 */
		public static function maybe_redirect() {
			$target = get_transient( self::REDIRECT_TRANSIENT );
			if ( ! $target ) {
				return;
			}
			delete_transient( self::REDIRECT_TRANSIENT );

			if ( wp_doing_ajax() || wp_doing_cron() || is_network_admin() ) {
				return;
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- bulk-activation marker only.
			if ( isset( $_GET['activate-multi'] ) ) {
				return;
			}
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Fresh installs that somehow already finished onboarding still land on the dashboard.
			if ( 'onboarding' === $target && get_option( self::COMPLETED_OPTION ) ) {
				$target = 'dashboard';
			}

			$page = ( 'onboarding' === $target )
				? self::PAGE_SLUG
				: ( class_exists( 'ECA_Dashboard_Page' ) ? ECA_Dashboard_Page::PAGE_SLUG : 'cool-plugins-events-addon' );

			wp_safe_redirect( admin_url( 'admin.php?page=' . $page ) );
			exit;
		}

		/**
		 * Admin URL for Single Page Builder setup.
		 *
		 * Prefers ECA_Addon_Map when the loaded copy has the helper; falls back
		 * when an older sibling addon defined ECA_Addon_Map first (class_exists
		 * guard) without spb_setup_admin_url().
		 *
		 * @return string
		 */
		public static function spb_setup_admin_url() {
			if ( class_exists( 'ECA_Addon_Map' ) && is_callable( array( 'ECA_Addon_Map', 'spb_setup_admin_url' ) ) ) {
				return ECA_Addon_Map::spb_setup_admin_url();
			}

			if ( class_exists( 'ESPBP_Onboarding', false ) ) {
				return admin_url( 'admin.php?page=' . ESPBP_Onboarding::PAGE_SLUG );
			}

			if ( class_exists( 'EPTA_Onboarding', false ) ) {
				return admin_url( 'admin.php?page=' . EPTA_Onboarding::PAGE_SLUG );
			}

			if ( post_type_exists( 'epta' ) ) {
				return admin_url( 'edit.php?post_type=epta' );
			}

			$page = class_exists( 'ECA_Dashboard_Page' ) ? ECA_Dashboard_Page::PAGE_SLUG : 'cool-plugins-events-addon';

			return admin_url( 'admin.php?page=' . $page );
		}

		/**
		 * Wizard preview state for Events Widgets (SPB Pro detection).
		 *
		 * @return array{preview_state: string, default_editor: string}
		 */
		public static function wizard_preview_state() {
			$spb_active = class_exists( 'ECA_Addon_Map' ) && ECA_Addon_Map::is_tier_active( 'spb', 'pro' );

			return array(
				'preview_state'  => $spb_active ? 'spb-installed' : 'default',
				'default_editor' => 'listing',
			);
		}

		/**
		 * @param string $classes Space-separated admin body classes.
		 * @return string
		 */
		public static function admin_body_class( $classes ) {
			if ( ! self::is_onboarding_screen() ) {
				return $classes;
			}

			return $classes . ' ectbe-onboarding-page';
		}

		/**
		 * @return bool
		 */
		private static function is_onboarding_screen() {
			if ( ! is_admin() ) {
				return false;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen detection.
			$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

			return self::PAGE_SLUG === $page;
		}

		/**
		 * Hidden submenu pages (null parent) can leave global $title unset.
		 * admin-header.php calls strip_tags( $title ), which deprecates on PHP 8.2+.
		 */
		public static function ensure_admin_page_title() {
			if ( ! self::is_onboarding_screen() ) {
				return;
			}

			global $title;

			if ( null === $title || '' === $title ) {
				$title = __( 'Setup — Events Widgets for Elementor', 'events-widgets-for-elementor-and-the-events-calendar' );
			}
		}

		/**
		 * @return void
		 */
		public static function suppress_foreign_notices() {
			if ( ! self::is_onboarding_screen() ) {
				return;
			}

			global $wp_filter;

			$current_hook = current_action();
			if ( empty( $current_hook ) || empty( $wp_filter[ $current_hook ] ) || ! ( $wp_filter[ $current_hook ] instanceof WP_Hook ) ) {
				return;
			}

			foreach ( $wp_filter[ $current_hook ]->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $callback ) {
					$function = $callback['function'] ?? null;

					if ( is_array( $function ) && isset( $function[0], $function[1] )
						&& $function[0] === __CLASS__ && 'suppress_foreign_notices' === $function[1] ) {
						continue;
					}

					if ( self::is_owned_notice_callback( $function ) ) {
						continue;
					}

					remove_action( $current_hook, $function, $priority );
				}
			}
		}

		/**
		 * @param callable|array|string|null $function Registered notice callback.
		 * @return bool
		 */
		private static function is_owned_notice_callback( $function ) {
			if ( ! is_array( $function ) || ! isset( $function[0] ) ) {
				return false;
			}

			$owner = $function[0];
			$class = is_object( $owner ) ? get_class( $owner ) : (string) $owner;

			return 0 === strpos( $class, 'ECTBE_' )
				|| 0 === strpos( $class, 'ECA_' )
				|| 0 === strpos( $class, 'Events_Calendar_Addon' );
		}

		/**
		 * Hidden admin page (URL / plugin action link only).
		 */
		public static function register_menu() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			add_submenu_page(
				null,
				__( 'Setup — Events Widgets for Elementor', 'events-widgets-for-elementor-and-the-events-calendar' ),
				__( 'Getting Started', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'manage_options',
				self::PAGE_SLUG,
				array( __CLASS__, 'render' )
			);
		}

		/**
		 * Render wizard markup.
		 */
		public static function render() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'events-widgets-for-elementor-and-the-events-calendar' ) );
			}

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted admin template from plugin package.
			echo self::get_wizard_markup();
		}

		/**
		 * Critical inline overrides (loads after linked styles in admin_head).
		 */
		public static function print_fullscreen_head() {
			if ( ! self::is_onboarding_screen() ) {
				return;
			}

			echo '<style id="ectbe-onboarding-fullscreen">'
				. 'html.wp-toolbar{padding-top:0!important;font-size:16px!important}'
				. 'body.ectbe-onboarding-page #wpcontent,body.ectbe-onboarding-page #wpbody,'
				. 'body.ectbe-onboarding-page #wpbody-content,body.ectbe-onboarding-page .wrap'
				. '{margin:0!important;margin-left:0!important;padding:0!important}'
				. '</style>' . "\n";
		}

		/**
		 * @param string $hook_suffix Admin hook.
		 */
		public static function enqueue( $hook_suffix ) {
			if ( false === strpos( $hook_suffix, self::PAGE_SLUG ) ) {
				return;
			}

			$base = ECTBE_URL . 'admin/ectbe-onboarding/assets/';
			$ver  = defined( 'ECA_DASHBOARD_VERSION' ) ? ECA_DASHBOARD_VERSION : ECTBE_VERSION;

			wp_enqueue_style( 'dashicons' );
			wp_enqueue_style( 'ectbe-onboarding-base', $base . 'css/ectbe-base.css', array( 'wp-admin', 'dashicons' ), $ver );
			wp_enqueue_style( 'ectbe-onboarding-wizard', $base . 'css/ectbe-wizard.css', array( 'ectbe-onboarding-base' ), $ver );
			wp_enqueue_style( 'ectbe-onboarding-admin', $base . 'css/ectbe-admin-wizard.css', array( 'ectbe-onboarding-wizard' ), $ver );

			$spb_setup_url = self::spb_setup_admin_url();

			wp_enqueue_script( 'ectbe-onboarding-wizard', $base . 'js/ectbe-wizard.js', array(), $ver, true );
			wp_enqueue_script( 'ectbe-onboarding-logic', $base . 'js/ectbe-onboarding.js', array( 'ectbe-onboarding-wizard' ), $ver, true );
			wp_enqueue_script( 'ectbe-onboarding-wp', $base . 'js/ectbe-wizard-wp.js', array( 'ectbe-onboarding-logic' ), $ver, true );

			wp_add_inline_script(
				'ectbe-onboarding-logic',
				'window.__ECTBE_SPB_WIZARD_URL=' . wp_json_encode( $spb_setup_url ) . ';',
				'before'
			);

			$images       = ECTBE_URL . 'admin/ectbe-onboarding/assets/images/';
			$icons        = ECTBE_URL . 'admin/eca-dashboard/assets/icons/';
			$editor_icons = self::editor_icon_urls();

			$editor_icons = array_merge(
				array(
					'elementor' => $images . 'elementor-icon.png',
				),
				is_array( $editor_icons ) ? $editor_icons : array()
			);

			$config = array(
				'slug'             => 'events-widgets',
				'steps'            => array(
					array( 'id' => 'mode', 'label' => 'Setup' ),
					array( 'id' => 'layout-query', 'label' => 'Layout & Query' ),
					array( 'id' => 'success', 'label' => 'Done' ),
				),
				'defaultTelemetry' => true,
				'assetBase'        => $images,
				'editorIcons'      => $editor_icons,
				'siblingIconBase'  => $icons,
				'summaryLabels'    => array(
					'mode'       => 'Setup',
					'layout'     => 'Layout',
					'style'      => 'Style',
					'time-range' => 'Time range',
					'category'   => 'Category',
				),
			);

			$preview = self::wizard_preview_state();

			wp_add_inline_script(
				'ectbe-onboarding-wizard',
				'window.ECTBE_WIZARD = ' . wp_json_encode( $config ) . ';' . "\n" . self::bootstrap_script( $preview['preview_state'], $preview['default_editor'] ),
				'before'
			);

			wp_localize_script(
				'ectbe-onboarding-logic',
				'ECTBE_ONBOARDING',
				array(
					'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
					'nonceComplete'   => wp_create_nonce( 'ectbe_onboarding_complete' ),
					'nonceCreatePage' => wp_create_nonce( 'ectbe_onboarding_create_page' ),
					'nonceSavePrefs'  => wp_create_nonce( 'ectbe_onboarding_save_preferences' ),
					'nonceActivate'   => wp_create_nonce( 'ectbe_onboarding_activate_pro' ),
					'dashboardUrl'    => admin_url( 'admin.php?page=' . ( class_exists( 'ECA_Dashboard_Page' ) ? ECA_Dashboard_Page::PAGE_SLUG : 'cool-plugins-events-addon' ) ),
					'spbWizardUrl'    => $spb_setup_url,
					'previewState'    => $preview['preview_state'],
					'pro'             => self::get_widgets_pro_state(),
					'telemetry'       => class_exists( 'ECTBE_Onboarding_Cpfm_Data' )
						? ECTBE_Onboarding_Cpfm_Data::get_telemetry_localize()
						: array(
							'show'    => true,
							'checked' => true,
							'choice'  => null,
						),
					'i18n'            => array(
						'upgradePro'   => __( 'Upgrade to Pro', 'events-widgets-for-elementor-and-the-events-calendar' ),
						'activatePro'  => __( 'Activate Pro', 'events-widgets-for-elementor-and-the-events-calendar' ),
						'activating'   => __( 'Activating…', 'events-widgets-for-elementor-and-the-events-calendar' ),
						'failedRetry'  => __( 'Failed — retry', 'events-widgets-for-elementor-and-the-events-calendar' ),
						'upgradeDesc'  => __( 'Upgrade to Events Widgets Pro to unlock everything you\'ve selected. Or pick free options to keep going.', 'events-widgets-for-elementor-and-the-events-calendar' ),
						'activateDesc' => __( 'You already own Events Widgets Pro — activate it to unlock everything you\'ve selected.', 'events-widgets-for-elementor-and-the-events-calendar' ),
					),
				)
			);
		}

		/**
		 * Events Widgets Pro on-disk state for Upgrade → Activate Pro.
		 *
		 * @return array{active: bool, installedInactive: bool, init: string, slug: string, proUrl: string, setupUrl: string}
		 */
		private static function get_widgets_pro_state() {
			$slug   = 'events-widgets-pro';
			$init   = '';
			$status = 'absent';

			if ( class_exists( 'ECA_Addon_Map' ) ) {
				$defs = ECA_Addon_Map::definitions();
				if ( ! empty( $defs['widgets']['pro'] ) ) {
					$slug = (string) $defs['widgets']['pro'];
				}
				$status = ECA_Addon_Map::tier_status( 'widgets', 'pro' );
				$init   = ECA_Addon_Map::tier_init( 'widgets', 'pro' );
				if ( ! $init && is_callable( array( 'ECA_Addon_Map', 'expected_tier_init' ) ) ) {
					$init = ECA_Addon_Map::expected_tier_init( 'widgets', 'pro' );
				}
			}

			$setup_url = class_exists( 'EWPE_Onboarding_Page', false )
				? admin_url( 'admin.php?page=' . EWPE_Onboarding_Page::PAGE_SLUG )
				: admin_url( 'admin.php?page=ewpe-onboarding' );

			return array(
				'active'            => ( 'active' === $status ),
				'installedInactive' => ( 'inactive' === $status && '' !== $init ),
				'init'              => $init,
				'slug'              => $slug,
				'proUrl'            => 'https://eventscalendaraddons.com/plugin/events-widgets-pro/',
				'setupUrl'          => $setup_url,
			);
		}

		/**
		 * @param string $preview_state  Wizard preview state slug.
		 * @param string $default_editor Default mode option value.
		 * @return string
		 */
		private static function bootstrap_script( $preview_state, $default_editor = 'listing' ) {
			$ps = esc_js( $preview_state );
			$de = esc_js( $default_editor );

			return "(function(){var ps='{$ps}';var de='{$de}';document.body.setAttribute('data-preview-state',ps);document.body.setAttribute('data-editor-selected',de);document.querySelectorAll('[data-states]').forEach(function(el){var states=(el.dataset.states||'').split(/\\s+/).filter(Boolean);el.hidden=states.indexOf(ps)===-1;});try{localStorage.removeItem('ectbe-preview-state:wizard-events-widgets');}catch(e){}try{var slug=window.ECTBE_WIZARD.slug;var raw=localStorage.getItem('ectbe:wizard:'+slug+':state');var editor=null;if(raw){var s=JSON.parse(raw);editor=s&&s.selections&&s.selections.editor;}if(!editor)editor=de;document.body.setAttribute('data-editor-selected',editor);var opt=document.querySelector('.ectbe-editor-option[data-value=\"'+editor+'\"]');if(opt){document.querySelectorAll('.ectbe-editor-option.is-selected').forEach(function(o){o.classList.remove('is-selected');});opt.classList.add('is-selected');}var dm=localStorage.getItem('ectbe:wizard:'+slug+':displayMode');if(dm)document.body.setAttribute('data-display-mode',dm);var pc=localStorage.getItem('ectbe:wizard:'+slug+':pageCreated');if(pc)document.body.setAttribute('data-page-created','true');}catch(e){}})();";
		}

		/**
		 * Load the PHP wizard view with escaped asset URLs.
		 *
		 * @return string
		 */
		private static function get_wizard_markup() {
			$path = ECTBE_PATH . 'admin/ectbe-onboarding/views/wizard-markup.php';
			if ( ! file_exists( $path ) ) {
				return '<p>' . esc_html__( 'Wizard template missing.', 'events-widgets-for-elementor-and-the-events-calendar' ) . '</p>';
			}

			$preview = self::wizard_preview_state();

			$dashboard_url = admin_url(
				'admin.php?page=' . ( class_exists( 'ECA_Dashboard_Page' ) ? ECA_Dashboard_Page::PAGE_SLUG : 'cool-plugins-events-addon' )
			);
			$icons           = ECTBE_URL . 'admin/eca-dashboard/assets/icons/';
			$images_fallback = ECTBE_URL . 'admin/ectbe-onboarding/assets/images/';
			$editor_icons    = self::editor_icon_urls();

			$editor_icons = array_merge(
				array(
					'elementor' => $images_fallback . 'elementor-icon.png',
				),
				is_array( $editor_icons ) ? $editor_icons : array()
			);

			$category_options = self::render_category_options_html();
			$preview_state    = isset( $preview['preview_state'] ) ? (string) $preview['preview_state'] : 'default';
			$default_editor   = isset( $preview['default_editor'] ) ? (string) $preview['default_editor'] : 'listing';
			$show_telemetry   = ! ( class_exists( 'ECTBE_Onboarding_Cpfm_Data' ) && ! ECTBE_Onboarding_Cpfm_Data::should_show_telemetry() );

			ob_start();
			include $path;
			return (string) ob_get_clean();
		}

		/**
		 * Build multiselect option markup from live tribe_events_cat terms.
		 *
		 * @return string
		 */
		private static function render_category_options_html() {
			$options = array(
				array(
					'value' => 'all',
					'label' => __( 'All events', 'events-widgets-for-elementor-and-the-events-calendar' ),
				),
			);

			if ( taxonomy_exists( 'tribe_events_cat' ) ) {
				$terms = get_terms(
					array(
						'taxonomy'   => 'tribe_events_cat',
						'hide_empty' => false,
						'orderby'    => 'name',
						'order'      => 'ASC',
					)
				);

				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					foreach ( $terms as $term ) {
						$options[] = array(
							'value' => (string) $term->term_id,
							'label' => $term->name,
						);
					}
				}
			}

			$html = '';
			foreach ( $options as $opt ) {
				$html .= sprintf(
					'<a href="#" role="option" class="ectbe-multiselect__option" data-value="%1$s" data-multiselect-option>' .
					'<span class="ectbe-multiselect__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>' .
					'<span class="ectbe-multiselect__label">%2$s</span>' .
					'</a>',
					esc_attr( $opt['value'] ),
					esc_html( $opt['label'] )
				);
			}

			return $html;
		}

		/**
		 * Whether the onboarding telemetry consent block should render.
		 *
		 * @return bool
		 */
		private static function should_show_telemetry_banner() {
			if ( class_exists( 'ECTBE_Onboarding_Cpfm_Data' ) ) {
				return ECTBE_Onboarding_Cpfm_Data::should_show_telemetry();
			}

			$choice = get_option( 'cpfm_opt_in_choice_cool_events', '' );

			return 'yes' !== $choice;
		}

		/**
		 * @param bool                 $accepted   Whether the telemetry checkbox was checked.
		 * @param array<string, mixed> $selections Wizard selections.
		 * @return void
		 */
		private static function handle_telemetry_choice( $accepted, $selections = array() ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			if ( ! class_exists( 'ECTBE_Onboarding_Cpfm_Data', false ) ) {
				require_once ECTBE_PATH . 'admin/ectbe-onboarding/includes/class-ectbe-onboarding-cpfm-data.php';
			}

			if ( class_exists( 'ECTBE_Onboarding_Cpfm_Data' ) ) {
				ECTBE_Onboarding_Cpfm_Data::save_choice( $accepted ? 'yes' : 'no' );
				ECTBE_Onboarding_Cpfm_Data::save_preferences( $accepted, $selections );
			} else {
				update_option( 'cpfm_opt_in_choice_cool_events', $accepted ? 'yes' : 'no', false );
			}

			if ( $accepted ) {
				do_action( 'cpfm_after_opt_in_ectbe', 'cool_events' );
				self::schedule_crons_for_other_plugins();
			}
		}

		private static function schedule_crons_for_other_plugins() {
			$plugin_files = (array) get_option( 'active_plugins', array() );
			if ( is_multisite() ) {
				$plugin_files = array_merge( $plugin_files, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
			}

			$self_slug = 'events-widgets-for-elementor-and-the-events-calendar';
			$seen      = array();

			foreach ( $plugin_files as $plugin_file ) {
				$slug = dirname( (string) $plugin_file );
				if ( '.' === $slug || '' === $slug || $self_slug === $slug || isset( $seen[ $slug ] ) ) {
					continue;
				}
				$seen[ $slug ] = true;
				do_action( 'plugin_opt_in_' . $slug );
			}
		}
		/**
		 * Persist Step-2 telemetry consent + wizard selections.
		 */
		public static function ajax_save_preferences() {
			check_ajax_referer( 'ectbe_onboarding_save_preferences', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'events-widgets-for-elementor-and-the-events-calendar' ) ) );
			}

			if ( ! class_exists( 'ECTBE_Onboarding_Cpfm_Data', false ) ) {
				require_once ECTBE_PATH . 'admin/ectbe-onboarding/includes/class-ectbe-onboarding-cpfm-data.php';
			}

			$telemetry_raw = isset( $_POST['telemetry'] ) ? sanitize_text_field( wp_unslash( $_POST['telemetry'] ) ) : '0';
			$telemetry     = in_array( $telemetry_raw, array( '1', 'yes', 'true' ), true );

			$selections = array();
			$allowed_keys = array( 'layout', 'time', 'style', 'category' );
			if ( isset( $_POST['selections'] ) ) {
				$raw = wp_unslash( $_POST['selections'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				if ( is_string( $raw ) ) {
					$decoded = json_decode( $raw, true );
					if ( is_array( $decoded ) ) {
						$selections = array_intersect_key(
						$decoded,
						array_flip( $allowed_keys )
			);
					}
				} elseif ( is_array( $raw ) ) {
					$selections = array_intersect_key( $raw, array_flip( $allowed_keys ) );
				}
			}

			self::handle_telemetry_choice( $telemetry, $selections );

			wp_send_json_success(
				array(
					'choice'    => $telemetry ? 'yes' : 'no',
					'telemetry' => class_exists( 'ECTBE_Onboarding_Cpfm_Data' )
						? ECTBE_Onboarding_Cpfm_Data::get_telemetry_localize()
						: array(
							'show'    => true,
							'checked' => true,
							'choice'  => null,
						),
				)
			);
		}

		/**
		 * Persist wizard completion.
		 */
		public static function ajax_complete() {
			check_ajax_referer( 'ectbe_onboarding_complete', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'events-widgets-for-elementor-and-the-events-calendar' ) ) );
			}

			update_option( self::COMPLETED_OPTION, true, false );

			wp_send_json_success(
				array(
					'redirect' => admin_url( 'admin.php?page=' . ( class_exists( 'ECA_Dashboard_Page' ) ? ECA_Dashboard_Page::PAGE_SLUG : 'cool-plugins-events-addon' ) ),
				)
			);
		}

		/**
		 * Activate Events Widgets Pro when it is installed but inactive.
		 */
		public static function ajax_activate_pro() {
			check_ajax_referer( 'ectbe_onboarding_activate_pro', 'nonce' );

			if ( ! current_user_can( 'activate_plugins' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'events-widgets-for-elementor-and-the-events-calendar' ) ) );
			}

			$pro   = self::get_widgets_pro_state();
			$init  = isset( $_POST['init'] ) ? sanitize_text_field( wp_unslash( $_POST['init'] ) ) : '';
			$allow = array_filter(
				array(
					$pro['init'],
					$pro['slug'] . '/events-widgets-pro.php',
					$pro['slug'] . '/' . $pro['slug'] . '.php',
				)
			);

			if ( '' === $init || ! in_array( $init, $allow, true ) ) {
				// Allow basename under the known Pro directory only.
				if ( '' === $init || dirname( $init ) !== $pro['slug'] ) {
					wp_send_json_error( array( 'message' => __( 'Plugin not allowed.', 'events-widgets-for-elementor-and-the-events-calendar' ) ) );
				}
			}

			if ( ! function_exists( 'activate_plugin' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$result = activate_plugin( $init );
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			$setup_url = class_exists( 'EWPE_Onboarding_Page', false )
				? admin_url( 'admin.php?page=' . EWPE_Onboarding_Page::PAGE_SLUG )
				: $pro['setupUrl'];

			wp_send_json_success(
				array(
					'message'  => __( 'Pro activated.', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'redirect' => $setup_url,
				)
			);
		}

		/**
		 * Create a draft Elementor page with the Events widget.
		 */
		public static function ajax_create_page() {
			check_ajax_referer( 'ectbe_onboarding_create_page', 'nonce' );

			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'events-widgets-for-elementor-and-the-events-calendar' ) ) );
			}

			$layout = isset( $_POST['layout'] ) ? sanitize_key( wp_unslash( $_POST['layout'] ) ) : 'list';
			$type   = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'upcoming';
			$title  = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';

			$telemetry_raw = isset( $_POST['telemetry'] ) ? sanitize_text_field( wp_unslash( $_POST['telemetry'] ) ) : '0';
			$telemetry     = in_array( $telemetry_raw, array( '1', 'yes', 'true' ), true );

			$categories = array( 'all' );
			if ( isset( $_POST['categories'] ) ) {
				$raw = wp_unslash( $_POST['categories'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				if ( is_string( $raw ) ) {
					$decoded = json_decode( $raw, true );
					if ( is_array( $decoded ) ) {
						$categories = array_map( 'sanitize_text_field', array_map( 'strval', $decoded ) );
					} else {
						$categories = array_filter( array_map( 'sanitize_title', explode( ',', $raw ) ) );
					}
				} elseif ( is_array( $raw ) ) {
					$categories = array_map( 'sanitize_title', $raw );
				}
			}

			if ( '' === $title ) {
				$layout_labels = array(
					'list'         => __( 'List', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'minimal-list' => __( 'Minimal List', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'calendar'     => __( 'Calendar', 'events-widgets-for-elementor-and-the-events-calendar' ),
				);
				$layout_label = $layout_labels[ $layout ] ?? __( 'Events', 'events-widgets-for-elementor-and-the-events-calendar' );
				$title        = sprintf(
					/* translators: %s: layout name */
					__( 'Events — %s', 'events-widgets-for-elementor-and-the-events-calendar' ),
					$layout_label
				);
			}

			$result = ECTBE_Onboarding_Draft_Page::create(
				array(
					'layout'     => $layout,
					'type'       => $type,
					'categories' => $categories,
					'title'      => $title,
				)
			);

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			self::handle_telemetry_choice(
				$telemetry,
				array(
					'layout' => $layout,
					'time'   => $type,
					'style'  => $type,
				)
			);

			wp_send_json_success(
				array_merge(
					$result,
					array(
						'telemetryAcknowledged' => $telemetry,
					)
				)
			);
		}
	}
}

<?php
/**
 * Shared Cool Events onboarding opt-in + preference storage.
 *
 * Contract for Events Calendar Addons (ECT and sibling onboardings):
 * - Consent:  option `cpfm_opt_in_choice_cool_events` = yes|no|unset
 * - Prefs:    option `cpfm_onboarding_preferences_cool_events` (autoload false)
 *
 * UI rules:
 * - choice === yes → hide telemetry block
 * - unset or no    → show telemetry, checkbox checked by default
 *
 * @package Template_Events_Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CPFM_Onboarding_Optin' ) ) {

	/**
	 * Cross-plugin onboarding telemetry helpers.
	 */
	final class CPFM_Onboarding_Optin {

		const CATEGORY          = 'cool_events';
		const CHOICE_OPTION     = 'cpfm_opt_in_choice_cool_events';
		const PREFERENCES_OPTION = 'cpfm_onboarding_preferences_cool_events';
		const PLUGIN_KEY_ECT    = 'ect';
		const PLUGIN_SLUG_ECT   = 'template-events-calendar';

		/**
		 * @return string|null 'yes'|'no'|null when unset.
		 */
		public static function get_choice() {
			$choice = get_option( self::CHOICE_OPTION, null );
			if ( 'yes' === $choice || 'no' === $choice ) {
				return $choice;
			}
			return null;
		}

		/**
		 * Whether the onboarding telemetry UI should be visible.
		 *
		 * @return bool
		 */
		public static function should_show_telemetry() {
			return 'yes' !== self::get_choice();
		}

		/**
		 * Default checked state when the telemetry UI is shown.
		 *
		 * @return bool Always true when shown (even after a prior "no").
		 */
		public static function get_default_checked() {
			return true;
		}

		/**
		 * Payload for wp_localize_script.
		 *
		 * @return array{show: bool, checked: bool, choice: string|null}
		 */
		public static function get_telemetry_localize() {
			return array(
				'show'    => self::should_show_telemetry(),
				'checked' => self::get_default_checked(),
				'choice'  => self::get_choice(),
			);
		}

		/**
		 * Persist shared Cool Events consent.
		 *
		 * @param string $yes_or_no 'yes' or 'no'.
		 */
		public static function save_choice( $yes_or_no ) {
			$choice = ( 'yes' === $yes_or_no ) ? 'yes' : 'no';
			update_option( self::CHOICE_OPTION, $choice, false );
		}

		/**
		 * Merge per-plugin onboarding preferences into the shared option.
		 *
		 * @param string               $plugin_key Short key (ect, widgets, …).
		 * @param array<string, mixed> $payload    Sanitized preference row.
		 */
		public static function save_preferences( $plugin_key, $payload ) {
			$plugin_key = sanitize_key( $plugin_key );
			if ( '' === $plugin_key || ! is_array( $payload ) ) {
				return;
			}

			$all = get_option( self::PREFERENCES_OPTION, array() );
			if ( ! is_array( $all ) ) {
				$all = array();
			}

			$all[ $plugin_key ] = $payload;
			update_option( self::PREFERENCES_OPTION, $all, false );
		}

		/**
		 * Preferences for cron extra_details. Empty unless opted in.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_preferences_for_extra_details() {
			if ( 'yes' !== self::get_choice() ) {
				return array();
			}

			$all = get_option( self::PREFERENCES_OPTION, array() );
			return is_array( $all ) ? $all : array();
		}

		/**
		 * Apply ECT opt-in: Settings flag, schedule cron, send once.
		 */
		public static function apply_opt_in_for_ect() {
			$ects_options = get_option( 'ects_options', array() );
			if ( ! is_array( $ects_options ) ) {
				$ects_options = array();
			}
			$ects_options['ect_cpfm_feedback_data'] = true;
			update_option( 'ects_options', $ects_options );

			if ( ! wp_next_scheduled( 'ect_extra_data_update' ) ) {
				wp_schedule_event( time(), 'every_30_days', 'ect_extra_data_update' );
			}

			if ( class_exists( 'ECT_cronjob' ) ) {
				ECT_cronjob::ect_send_data();
			}
		}

		/**
		 * Allowlisted selection keys from the ECT wizard.
		 *
		 * @return string[]
		 */
		public static function ect_selection_allowlist() {
			return array(
				'editor',
				'layout',
				'style',
				'filter-bar',
				'time',
				'featured',
				'taxonomy',
				'category',
				'date-format',
				'displayMode',
			);
		}

		/**
		 * Sanitize raw wizard selections for storage / telemetry.
		 *
		 * @param mixed $raw Decoded JSON / array.
		 * @return array<string, string|bool|array>
		 */
		public static function sanitize_ect_selections( $raw ) {
			if ( ! is_array( $raw ) ) {
				return array();
			}

			$out       = array();
			$allowlist = self::ect_selection_allowlist();

			foreach ( $allowlist as $key ) {
				if ( ! array_key_exists( $key, $raw ) ) {
					continue;
				}
				$value = $raw[ $key ];

				if ( is_array( $value ) ) {
					$clean = array();
					if ( isset( $value['value'] ) ) {
						$clean['value'] = sanitize_text_field( (string) $value['value'] );
					}
					if ( isset( $value['label'] ) ) {
						$clean['label'] = sanitize_text_field( (string) $value['label'] );
					}
					if ( isset( $value['isPro'] ) ) {
						$clean['isPro'] = (bool) $value['isPro'];
					}
					if ( ! empty( $clean ) ) {
						$out[ $key ] = $clean;
					}
					continue;
				}

				if ( is_bool( $value ) ) {
					$out[ $key ] = $value;
					continue;
				}

				$out[ $key ] = sanitize_text_field( (string) $value );
			}

			return $out;
		}

		/**
		 * Build the ECT preference row.
		 *
		 * @param bool                 $telemetry  Whether user accepted sharing.
		 * @param array<string, mixed> $selections Sanitized selections.
		 * @return array<string, mixed>
		 */
		public static function build_ect_preference_row( $telemetry, $selections ) {
			return array(
				'plugin'     => self::PLUGIN_SLUG_ECT,
				'updated_at' => gmdate( 'Y-m-d H:i:s' ),
				'telemetry'  => (bool) $telemetry,
				'selections' => is_array( $selections ) ? $selections : array(),
			);
		}
	}
}

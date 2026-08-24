<?php
/**
 * Events Widgets for Elementor (Free) — onboarding data for the shared CPFM payload.
 *
 * Plugin-specific configuration only. CPFM keeps the common
 * consent / cron / environment logic; this class:
 * - stores wizard selections
 * - appends them onto extra_details via `cpfm_environment`
 * - writes the existing Cool Events consent option
 *
 * @package Events_Widgets_For_Elementor_And_The_Events_Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECTBE_Onboarding_Cpfm_Data' ) ) {

	/**
	 * Widgets Free onboarding data appended to the existing CPFM snapshot.
	 */
	final class ECTBE_Onboarding_Cpfm_Data {

		const CHOICE_OPTION      = 'cpfm_opt_in_choice_cool_events';
		const PREFERENCES_OPTION = 'cpfm_onboarding_preferences_cool_events';
		const PLUGIN_KEY         = 'ectbe';
		const PLUGIN_SLUG        = 'events-widgets-for-elementor-and-the-events-calendar';

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
		 * Payload for wp_localize_script.
		 *
		 * @return array{show: bool, checked: bool, choice: string|null}
		 */
		public static function get_telemetry_localize() {
			return array(
				'show'    => self::should_show_telemetry(),
				'checked' => true,
				'choice'  => self::get_choice(),
			);
		}

		/**
		 * Persist shared Cool Events consent (existing CPFM option).
		 *
		 * @param string $yes_or_no 'yes' or 'no'.
		 * @return void
		 */
		public static function save_choice( $yes_or_no ) {
			$choice = ( 'yes' === $yes_or_no ) ? 'yes' : 'no';
			update_option( self::CHOICE_OPTION, $choice, false );
		}

		/**
		 * Sanitize wizard selections and merge this plugin's row into the
		 * shared onboarding-preferences option.
		 *
		 * @param bool                 $telemetry  Whether the user accepted sharing.
		 * @param array<string, mixed> $selections Raw wizard selections.
		 * @return array<string, mixed>
		 */
		public static function save_preferences( $telemetry, $selections ) {
			$row = array(
				'plugin'     => self::PLUGIN_SLUG,
				'updated_at' => gmdate( 'Y-m-d H:i:s' ),
				'telemetry'  => (bool) $telemetry,
				'selections' => self::sanitize_selections( $selections ),
			);

			$all = get_option( self::PREFERENCES_OPTION, array() );
			if ( ! is_array( $all ) ) {
				$all = array();
			}

			$all[ self::PLUGIN_KEY ] = $row;
			update_option( self::PREFERENCES_OPTION, $all, false );

			return $row;
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
		 * Allowlisted selection keys from the ECTBE wizard.
		 *
		 * @return string[]
		 */
		public static function selection_allowlist() {
			return array(
				'editor',
				'layout',
				'style',
				'filter-bar',
				'time',
				'category',
				'displayMode',
			);
		}

		/**
		 * Sanitize raw wizard selections for storage / telemetry.
		 *
		 * @param mixed $raw Decoded JSON / array.
		 * @return array<string, string|bool|array>
		 */
		public static function sanitize_selections( $raw ) {
			if ( ! is_array( $raw ) ) {
				return array();
			}

			$out       = array();
			$allowlist = self::selection_allowlist();

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
	}
}

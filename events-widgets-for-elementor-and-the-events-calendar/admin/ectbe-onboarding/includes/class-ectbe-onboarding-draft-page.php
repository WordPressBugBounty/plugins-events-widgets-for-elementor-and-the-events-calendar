<?php
/**
 * Creates draft Elementor pages with the Events Widget for onboarding.
 *
 * @package Events_Widgets_For_Elementor_And_The_Events_Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECTBE_Onboarding_Draft_Page' ) ) {

	/**
	 * Elementor draft page helper for the ECTBE wizard.
	 */
	final class ECTBE_Onboarding_Draft_Page {

		/**
		 * @param array<string, mixed> $args layout, type, categories, title.
		 * @return array<string, string>|WP_Error
		 */
		public static function create( $args ) {
			if ( ! class_exists( '\Elementor\Plugin' ) ) {
				return new WP_Error( 'ectbe_no_elementor', __( 'Elementor is not available.', 'events-widgets-for-elementor-and-the-events-calendar' ) );
			}

			$layout     = isset( $args['layout'] ) ? sanitize_key( (string) $args['layout'] ) : 'list';
			$type       = isset( $args['type'] ) ? sanitize_key( (string) $args['type'] ) : 'future';
			$categories = isset( $args['categories'] ) && is_array( $args['categories'] ) ? $args['categories'] : array( 'all' );
			$title      = isset( $args['title'] ) ? sanitize_text_field( (string) $args['title'] ) : __( 'Events', 'events-widgets-for-elementor-and-the-events-calendar' );

			$allowed_layouts = array( 'list', 'minimal-list', 'calendar' );
			if ( ! in_array( $layout, $allowed_layouts, true ) ) {
				$layout = 'list';
			}

			$type_map = array(
				'upcoming' => 'future',
				'past'     => 'past',
				'both'     => 'all',
				'future'   => 'future',
				'all'      => 'all',
			);
			$ectbe_type = $type_map[ $type ] ?? 'future';

			$cat_values = self::normalize_category_ids( $categories );

			$settings = array(
				'ectbe_layout'        => $layout,
				'ectbe_type'          => $ectbe_type,
				'ectbe_ev_category'   => $cat_values,
				'ectbe_event_source'  => 'all',
				'ectbe_styles'        => 'style-1',
				'ectbe_max_events'    => 25,
			);

			$widget = array(
				'id'         => self::elementor_id(),
				'elType'     => 'widget',
				'widgetType' => 'the-events-calendar-addon',
				'settings'   => $settings,
				'elements'   => array(),
			);

			$column = array(
				'id'       => self::elementor_id(),
				'elType'   => 'column',
				'isInner'  => false,
				'settings' => array(
					'_column_size' => 100,
				),
				'elements' => array( $widget ),
			);

			$section = array(
				'id'       => self::elementor_id(),
				'elType'   => 'section',
				'isInner'  => false,
				'settings' => array(),
				'elements' => array( $column ),
			);

			$post_id = wp_insert_post(
				array(
					'post_title'  => $title,
					'post_status' => 'draft',
					'post_type'   => 'page',
					'post_author' => get_current_user_id(),
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}

			$json = wp_json_encode( array( $section ) );
			if ( ! is_string( $json ) ) {
				return new WP_Error( 'ectbe_elementor_json', __( 'Could not build Elementor data.', 'events-widgets-for-elementor-and-the-events-calendar' ) );
			}

			update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
			update_post_meta( $post_id, '_elementor_template_type', 'wp-page' );
			update_post_meta( $post_id, '_elementor_data', wp_slash( $json ) );

			if ( defined( 'ELEMENTOR_VERSION' ) ) {
				update_post_meta( $post_id, '_elementor_version', ELEMENTOR_VERSION );
			}

			delete_post_meta( $post_id, '_elementor_css' );

			$preview_url = get_preview_post_link( $post_id );
			$edit_url    = admin_url( 'post.php?post=' . $post_id . '&action=elementor' );

			return array(
				'postId'     => (string) $post_id,
				'previewUrl' => $preview_url ? $preview_url : get_permalink( $post_id ),
				'editUrl'    => $edit_url,
				'viewUrl'    => get_permalink( $post_id ),
			);
		}

		/**
		 * Normalize wizard category values to the term IDs the widget expects.
		 *
		 * The Elementor control stores tribe_events_cat term IDs (see
		 * ectbe_get_tags), but the wizard may submit numeric IDs or legacy slugs.
		 *
		 * @param array<int|string> $categories Raw category values from the wizard.
		 * @return array<int|string>
		 */
		private static function normalize_category_ids( $categories ) {
			if ( empty( $categories ) || in_array( 'all', $categories, true ) ) {
				return array( 'all' );
			}

			$ids = array();

			foreach ( $categories as $cat ) {
				if ( is_numeric( $cat ) ) {
					$term_id = absint( $cat );
					if ( $term_id > 0 && term_exists( $term_id, 'tribe_events_cat' ) ) {
						$ids[] = $term_id;
					}
					continue;
				}

				$slug = sanitize_title( (string) $cat );
				if ( '' === $slug ) {
					continue;
				}

				$term = get_term_by( 'slug', $slug, 'tribe_events_cat' );
				if ( $term && ! is_wp_error( $term ) ) {
					$ids[] = (int) $term->term_id;
				}
			}

			if ( empty( $ids ) ) {
				return array( 'all' );
			}

			return array_values( array_unique( $ids ) );
		}

		/**
		 * @return string
		 */
		private static function elementor_id() {
			return dechex( wp_rand( 0x100000, 0xFFFFFF ) );
		}
	}
}

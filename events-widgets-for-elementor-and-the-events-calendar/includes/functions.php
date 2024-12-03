<?php
function ectbe_get_tags( $args = array() ) {
		$options        = array();
		$tags           = get_tags( $args );
		$options['all'] = 'All';
	if ( is_wp_error( $tags ) ) {
		return array();
	}
	foreach ( $tags as $tag ) {
		$options[ $tag->term_id ] = $tag->name;
	}
	return $options;
}
function ectbe_get_the_events_calendar_events( $settings ) {
	if ( ! function_exists( 'tribe_get_events' ) ) {
		return array();
	}
	$meta_date_compare = '>=';
	if ( $settings['ectbe_type'] == 'past' ) {
		$meta_date_compare = '<';
	} elseif ( $settings['ectbe_type'] == 'all' ) {
		$meta_date_compare = '';
	}
		$attribute['key']       = '_EventStartDate';
		$attribute['meta_date'] = '';
		$meta_date_date         = '';
	if ( $meta_date_compare != '' ) {
		$meta_date_date         = current_time( 'Y-m-d H:i:s' );
		$attribute['key']       = '_EventStartDate';
		$attribute['meta_date'] = array(
			array(
				'key'     => '_EventEndDate',
				'value'   => $meta_date_date,
				'compare' => $meta_date_compare,
				'type'    => 'DATETIME',
			),
		);
	}
	$ect_args = apply_filters(
		'ectbe_args_filter',
		array(
			'post_status'    => 'publish',
			'posts_per_page' => $settings['ectbe_max_events'],
			'meta_key'       => $attribute['key'],
			'orderby'        => 'event_date',
			'order'          => $settings['ectbe_order'],
			'meta_query'     => $attribute['meta_date'],
		),
		$attribute,
		$meta_date_date,
		$meta_date_compare
	);
	if ( ! empty( $settings['ectbe_ev_category'] ) ) {
		if ( ! in_array( 'all', $settings['ectbe_ev_category'] ) ) {
			   $ect_args['tax_query'] = array(
				   array(
					   'taxonomy' => 'tribe_events_cat',
					   'field'    => 'id',
					   'terms'    => $settings['ectbe_ev_category'],
				   ),
			   );
		}
	}
	if ( ! empty( $settings['ectbe_date_range_start'] ) ) {
		$newStartDate           = gmdate( 'Y-m-d', strtotime( $settings['ectbe_date_range_start'] ) );
		$ect_args['start_date'] = $newStartDate;
	}
	if ( ! empty( $settings['ectbe_date_range_end'] ) ) {
		$newEndDate           = gmdate( 'Y-m-d', strtotime( $settings['ectbe_date_range_end'] ) );
		$ect_args['end_date'] = $newEndDate;
	}
	$date_format = $settings['ectbe_date_formats'];
	$events      = tribe_get_events( $ect_args );
	if ( empty( $events ) ) {
		 return array();
	}
	$calendar_data = array();
	foreach ( $events as $key => $event ) {
		$all_day = 'yes';
		if ( ! tribe_event_is_all_day( $event->ID ) ) {
			$all_day = '';
		}
		// $description     = mb_strimwidth( $event->post_content, 0, 150, '...' );
		$description     = substr( $event->post_content, 0, 150 );
		$imgurl          = ectbe_get_event_image( $event->ID );
		$eventCost       = tribe_get_cost( $event->ID, true );
		$template        = '';
		$event_schedule  = ectbe_event_schedule( $event->ID, $date_format, $template );
		$ev_time         = ectbe_tribe_event_time( $event->ID, false );
		$calendar_data[] = array(
			'id'             => $event->ID,
			'title'          => ! empty( $event->post_title ) ? $event->post_title : __(
				'No Title',
				'ectbe'
			),
			'start'          => tribe_get_start_date( $event->ID, true, $date_format ),
			'end'            => tribe_get_end_date( $event->ID, true, $date_format ),
			// 'borderColor' => !empty($settings['ectbe_event_global_popup_ribbon_color']) ? $settings['ectbe_event_global_popup_ribbon_color'] : '#10ecab',
			'textColor'      => $settings['ectbe_calendar_text_color'],
			'color'          => $settings['ectbe_calendar_bg_color'],
			'url'            => ( $settings['ectbe_hide_read_more_link'] !== 'yes' ) ? tribe_get_event_link( $event->ID ) : '',
			'allDay'         => $all_day,
			'description'    => $description,
			'eventimgurl'    => $imgurl,
			'eventcost'      => $eventCost,
			'event_schedule' => $event_schedule,
			'ev_time'        => $ev_time,
		);
	}
	return $calendar_data;
}
// get event featured image url
function ectbe_get_event_image( $event_id ) {
	$ev_post_img  = '';
	$feat_img_url = wp_get_attachment_image_src( get_post_thumbnail_id( $event_id ), 'large' );
	if ( ! empty( $feat_img_url ) && $feat_img_url[0] != false ) {
		$ev_post_img = $feat_img_url[0];
	}
	return $ev_post_img;
}
function ectbe_display_category( $event_id ) {
	$ectbe_cate = '';
	$ectbe_cate = get_the_term_list( $event_id, 'tribe_events_cat', '<ul class="ectbe-evt-category"><li class="ectbe-each-cate">', '</li><li class="ectbe-each-cate">', '</li></ul>' );
	return $ectbe_cate;
}
// generate events dates html
function ectbe_event_schedule( $event_id, $date_format, $template ) {
	   /*Date Format START*/
	$event_schedule = '';
	$ev_time        = ectbe_tribe_event_time( $event_id, false );
	if ( $date_format == 'DM' ) {
		  $event_schedule = '<div class="ectbe-date-area">
                                <span class="ectbe-ev-day">' . esc_attr( tribe_get_start_date( $event_id, false, 'd' ) ) . '</span>
                                <span class="ectbe-ev-mo">' . esc_attr( tribe_get_start_date( $event_id, false, 'M' ) ) . '</span>
							
                                </div>';

	} elseif ( $date_format == 'DML' ) {
		$event_schedule = '<div class="ectbe-date-area">
		<span class="ectbe-ev-day">' . esc_html( tribe_get_start_date( $event_id, false, 'd' ) ) . '</span>
		<span class="ectbe-ev-mo">' . esc_html( tribe_get_start_date( $event_id, false, 'M' ) ) . '</span>
		<span class="ectbe-week-day">' . esc_html( tribe_get_start_date( $event_id, false, 'l' ) ) . '</span>
		</div>';

	} elseif ( $date_format == 'MD' ) {
		$event_schedule = '<div class="ectbe-date-area">
						   <span class="ectbe-ev-mo">' . esc_html( tribe_get_start_date( $event_id, false, 'M' ) ) . '</span>
						   <span class="ectbe-ev-day">' . esc_html( tribe_get_start_date( $event_id, false, 'd' ) ) . '</span>
						   </div>';

	} elseif ( $date_format == 'FD' ) {
		$event_schedule = '<div class="ectbe-date-area">
                                <span class="ectbe-ev-mo">' . esc_html( tribe_get_start_date( $event_id, false, 'F' ) ) . '</span>
                                <span class="ectbe-ev-day">' . esc_html( tribe_get_start_date( $event_id, false, 'd' ) ) . '</span>
                                </div>';

	} elseif ( $date_format == 'DF' ) {
		$event_schedule = '<div class="ectbe-date-area">
                                <span class="ectbe-ev-day">' . esc_html( tribe_get_start_date( $event_id, false, 'd' ) ) . '</span>
                                <span class="ectbe-ev-mo">' . esc_html( tribe_get_start_date( $event_id, false, 'F' ) ) . '</span>
                                </div>';

	} elseif ( $date_format == 'FD,Y' ) {
		$event_schedule = '<div class="ectbe-date-area">
                                <span class="ectbe-ev-mo">' . esc_html( tribe_get_start_date( $event_id, false, 'F' ) ) . '</span>
                                <span class="ectbe-ev-day">' . esc_html( tribe_get_start_date( $event_id, false, 'd' ) ) . ', </span>
                                <span class="ectbe-ev-yr">' . esc_html( tribe_get_start_date( $event_id, false, 'Y' ) ) . '</span>
                                </div>';

	} elseif ( $date_format == 'MD,Y' ) {
		$event_schedule = '<div class="ectbe-date-area">
                                <span class="ectbe-ev-mo">' . esc_html( tribe_get_start_date( $event_id, false, 'M' ) ) . '</span>
                                <span class="ectbe-ev-day">' . esc_html( tribe_get_start_date( $event_id, false, 'd' ) ) . ', </span>
                                <span class="ectbe-ev-yr">' . esc_html( tribe_get_start_date( $event_id, false, 'Y' ) ) . '</span>
                                </div>';

	} elseif ( $date_format == 'MD,YT' ) {
		$event_schedule = '<div class="ectbe-date-area">
                                <span class="ectbe-ev-mo">' . tribe_get_start_date( $event_id, false, 'M' ) . '</span>
                                <span class="ectbe-ev-day">' . tribe_get_start_date( $event_id, false, 'd' ) . ', </span>
                                <span class="ectbe-ev-yr">' . tribe_get_start_date( $event_id, false, 'Y' ) . '</span>
                                <span class="ectbe-evt-time"><span class="ectbe-icon"><i class="ectbe-icon-clock" aria-hidden="true"></i></span> ' . $ev_time . '</span>
                                </div>';

	} elseif ( $date_format == 'jMl' ) {
		$event_schedule = '<div class="ectbe-date-area">
                                <span class="ectbe-ev-day">' . esc_html( tribe_get_start_date( $event_id, false, 'j' ) ) . '</span>
                                <span class="ectbe-ev-mo">' . esc_html( tribe_get_start_date( $event_id, false, 'M' ) ) . '</span>
                                <span class="ectbe-week-day">' . esc_html( tribe_get_start_date( $event_id, false, 'l' ) ) . '</span>
                                </div>';

	} elseif ( $date_format == 'full' ) {
		$event_schedule = '<div class="ectbe-date-area">
                                <span class="ectbe-ev-day">' . esc_html( tribe_get_start_date( $event_id, false, 'd' ) ) . '</span>
                                <span class="ectbe-ev-mo">' . esc_html( tribe_get_start_date( $event_id, false, 'F' ) ) . '</span>
                                <span class="ectbe-ev-yr">' . esc_html( tribe_get_start_date( $event_id, false, 'Y' ) ) . '</span>
                                <span class="ectbe-evt-time"><span class="ectbe-icon"><i class="ectbe-icon-clock" aria-hidden="true"></i></span> ' . esc_html( $ev_time ) . '</span>
                                </div>';

	} elseif ( $date_format == 'd.FY' ) {
		$event_schedule = '<div class="ectbe-date-area">
                                <span class="ectbe-ev-day">' . esc_html( tribe_get_start_date( $event_id, false, 'd' ) ) . '. </span>
                                <span class="ectbe-ev-mo">' . esc_html( tribe_get_start_date( $event_id, false, 'F' ) ) . '</span>
                                <span class="ectbe-ev-yr">' . esc_html( tribe_get_start_date( $event_id, false, 'Y' ) ) . '</span>
                                </div>';

	} elseif ( $date_format == 'd.F' ) {
		$event_schedule = '<div class="ectbe-date-area">
                                <span class="ectbe-ev-day">' . esc_html( tribe_get_start_date( $event_id, false, 'd' ) ) . '. </span>
                                <span class="ectbe-ev-mo">' . esc_html( tribe_get_start_date( $event_id, false, 'F' ) ) . '</span>
                                </div>';

	} elseif ( $date_format == 'd.Ml' ) {
		$event_schedule = '<div class="ectbe-date-area">
                                <span class="ectbe-ev-day">' . esc_html( tribe_get_start_date( $event_id, false, 'd' ) ) . '. </span>
                                <span class="ectbe-ev-mo">' . esc_html( tribe_get_start_date( $event_id, false, 'M' ) ) . '</span>
                                <span class="ectbe-week-day">' . esc_html( tribe_get_start_date( $event_id, false, 'l' ) ) . '</span>
                                </div>';

	} elseif ( $date_format == 'ldF' ) {
		$event_schedule = '<div class="ectbe-date-area">
                                <span class="ectbe-week-day">' . esc_html( tribe_get_start_date( $event_id, false, 'l' ) ) . '</span>
                                <span class="ectbe-ev-day">' . esc_html( tribe_get_start_date( $event_id, false, 'd' ) ) . '</span>
                                <span class="ectbe-ev-mo">' . esc_html( tribe_get_start_date( $event_id, false, 'F' ) ) . '</span>
                                </div>';

	} elseif ( $date_format == 'Mdl' ) {
		$event_schedule = '<div class="ectbe-date-area">
                                <span class="ectbe-ev-day">' . esc_html( tribe_get_start_date( $event_id, false, 'M' ) ) . '</span>
                                <span class="ectbe-ev-mo">' . esc_html( tribe_get_start_date( $event_id, false, 'd' ) ) . '</span>
                                <span class="ectbe-week-day">' . esc_html( tribe_get_start_date( $event_id, false, 'l' ) ) . '</span>
                                </div>';

	} elseif ( $date_format == 'dFT' ) {
		$event_schedule = '<div class="ectbe-date-area">
                                <span class="ectbe-ev-day">' . esc_html( tribe_get_start_date( $event_id, false, 'd' ) ) . '</span>
                                <span class="ectbe-ev-mo">' . esc_html( tribe_get_start_date( $event_id, false, 'F' ) ) . '</span>
                                <span class="ectbe-evt-time"><span class="ectbe-icon"><i class="ectbe-icon-clock" aria-hidden="true"></i></span> ' . esc_html( $ev_time ) . '</span>
                                </div>';

	} elseif ( $date_format == 'custom' ) {
		$event_schedule = '<span class="ectbe-custom-schedule">' . esc_html( tribe_events_event_schedule_details( $event_id ) ) . '</span>';
	} elseif ( $date_format == 'start_end' ) {
		$event_schedule = '<div class="ectbe-date-area">
		<span class="ectbe-ev-day">' . esc_html( tribe_get_start_date( $event_id, false, 'd' ) ) . '</span>
		<span class="ectbe-ev-mo">' . esc_html( tribe_get_start_date( $event_id, false, 'F' ) ) . '</span>
		<span class="ectbe-ev-yr">' . esc_html( tribe_get_start_date( $event_id, false, 'Y' ) ) . '</span>';
		if ( ( tribe_get_start_date( $event_id, false, 'd' ) == tribe_get_end_date( $event_id, false, 'd' ) ) && ( tribe_get_start_date( $event_id, false, 'F' ) == tribe_get_end_date( $event_id, false, 'F' ) ) && ( tribe_get_start_date( $event_id, false, 'Y' ) == tribe_get_start_date( $event_id, false, 'Y' ) ) ) {

		} else {

			$event_schedule .= '<span class="ectbe-ev-hyphon"> - </span>
			<span class="ectbe-ev-day">' . esc_attr( tribe_get_end_date( $event_id, false, 'd' ) ) . '</span>
			<span class="ectbe-ev-mo">' . esc_attr( tribe_get_end_date( $event_id, false, 'F' ) ) . '</span>
			<span class="ectbe-ev-yr">' . esc_html( tribe_get_end_date( $event_id, false, 'Y' ) ) . '</span>';
		}
		$event_schedule .= '</div>';
	} else {

		$event_schedule = '<div class="ectbe-date-area">
                                <span class="ectbe-ev-day">' . esc_html( tribe_get_start_date( $event_id, false, 'd' ) ) . '</span>
                                <span class="ectbe-ev-mo">' . esc_html( tribe_get_start_date( $event_id, false, 'F' ) ) . '</span>
                                <span class="ectbe-ev-yr">' . esc_html( tribe_get_start_date( $event_id, false, 'Y' ) ) . '</span>
                                </div>';

	}
	return $event_schedule;
}
// grab events time for later use
function ectbe_tribe_event_time( $post_id, $display = true ) {
	 $event = $post_id;
	if ( tribe_event_is_all_day( $event ) ) { // all day event
		if ( $display ) {
			esc_html_e( 'All day', 'ectbe' );
		} else {
			return esc_html__( 'All day', 'ectbe' );
		}
	} elseif ( tribe_event_is_multiday( $event ) ) { // multi-date event
		   $start_date = tribe_get_start_date( $event, false, false );
		   $end_date   = tribe_get_end_date( $event, false, false );
		if ( $display ) {
			printf( '%1$s - %2$s', esc_html( $start_date ), esc_html( $end_date ) );
		} else {
			return sprintf( '%1$s - %2$s', esc_html( $start_date ), esc_html( $end_date ) );
		}
	} else {
		$time_format = get_option( 'time_format' );
		$start_date  = tribe_get_start_date( $event, false, $time_format );
		$end_date    = tribe_get_end_date( $event, false, $time_format );
		if ( $start_date !== $end_date ) {
			if ( $display ) {
				printf( '%1$s - %2$s', esc_html( $start_date ), esc_html( $end_date ) );
			} else {
				return sprintf( '%1$s - %2$s', esc_html( $start_date ), esc_html( $end_date ) );
			}
		} else {
			if ( $display ) {
				printf( '%s', esc_html( $start_date ) );
			} else {
				return sprintf( '%s', esc_html( $start_date ) );
			}
		}
	}
}
// compatibility for < 1.5.2 versions
function ectbe_older_v_compatibility( $post_id, $settings, $layout, $widget_id ) {
	$custom_styles = '';
	$widgetID      = '.elementor-' . $post_id . ' .elementor-element.elementor-element-' . $widget_id;
	$selector      = $widgetID . ' .ectbe-' . $layout . '-wrapper';
	$typo_index    = '_typography';
	if ( isset( $settings['ectbe_main_skin_color'] ) && $settings['ectbe_main_skin_color'] != '' ) {
		$custom_styles .= $selector . '{--e-ectbe-date-area-background:' . $settings['ectbe_main_skin_color'] . ';}';
	}
	if ( isset( $settings['ectbe_featured_skin_color'] ) && $settings['ectbe_featured_skin_color'] != '' ) {
		$custom_styles .= $selector . '{--ectbe-featd-evt-bg-color:' . $settings['ectbe_featured_skin_color'] . ';}';
	}
	if ( isset( $settings['ectbe_event_bgcolor'] ) && $settings['ectbe_event_bgcolor'] != '' ) {
		$custom_styles .= $selector . '{--e-ectbe-content-box-background:' . $settings['ectbe_event_bgcolor'] . ';}';
	}
	if ( isset( $settings['ectbe_featured_font_color'] ) && $settings['ectbe_featured_font_color'] != '' ) {
		$custom_styles .= $selector . '{--ectbe-featd-evt-color:' . $settings['ectbe_featured_font_color'] . ';}';
	}
	if ( isset( $settings['ectbe_date_color'] ) && $settings['ectbe_date_color'] != '' ) {
		$custom_styles .= $selector . '{--e-ectbe-date-area-color:' . $settings['ectbe_date_color'] . ';}';
	}
	if ( isset( $settings['ectbe_title_color'] ) && $settings['ectbe_title_color'] != '' ) {
		$custom_styles .= $selector . '{--e-ectbe-evt-title-color:' . $settings['ectbe_title_color'] . ';}';
	}
	if ( isset( $settings['ectbe_desc_color'] ) && $settings['ectbe_desc_color'] != '' ) {
		$custom_styles .= $selector . '{--e-ectbe-evt-description-color:' . $settings['ectbe_desc_color'] . ';}';
	}
	if ( isset( $settings['ectbe_venue_color'] ) && $settings['ectbe_venue_color'] != '' ) {
		$custom_styles .= $selector . '{--e-ectbe-evt-venue-color:' . $settings['ectbe_venue_color'] . ';}';
	}
	if ( isset( $settings['ectbe_read_more_color'] ) && $settings['ectbe_read_more_color'] != '' ) {
		$custom_styles .= $selector . '{--e-ectbe-evt-read-more-color:' . $settings['ectbe_read_more_color'] . ';}';
	}
	$title_key = 'ectbe_title_typography';
	if ( isset( $settings[ $title_key . $typo_index ] ) &&
		  $settings[ $title_key . $typo_index ] == 'custom' ) {
		$title_styles   = get_typography_settings( $title_key, $settings );
		$custom_styles .= $widgetID . ' .ectbe-evt-title .ectbe-evt-url{' . $title_styles . '}';

	}
	 $desc_key = 'ectbe_desc_typography';
	if ( isset( $settings[ $desc_key . $typo_index ] ) &&
		 $settings[ $desc_key . $typo_index ] == 'custom' ) {
		$desc_styles    = get_typography_settings( $desc_key, $settings );
		$custom_styles .= $widgetID . ' .ectbe-evt-description{' . $desc_styles . '}';
	}
	$date_key = 'ectbe_date_typography';
	if ( isset( $settings[ $date_key . $typo_index ] ) &&
		 $settings[ $date_key . $typo_index ] == 'custom' ) {
		$date_styles    = get_typography_settings( $date_key, $settings );
		$custom_styles .= $widgetID . ' .ectbe-list-wrapper.style-1 .ectbe-date-area{' . $date_styles . '}';
		$custom_styles .= $widgetID . ' .ectbe-content-box .ectbe-date-area span{' . $date_styles . '}';
		$custom_styles .= $widgetID . ' .ectbe-minimal-list-wrapper .ectbe-evt-time {' . $date_styles . '}';
	}
	$veune_key = 'ectbe_venue_typography';
	if ( isset( $settings[ $veune_key . $typo_index ] ) &&
		 $settings[ $veune_key . $typo_index ] == 'custom' ) {
		$venue_styles   = get_typography_settings( $veune_key, $settings );
		$custom_styles .= $widgetID . ' .ectbe-evt-venue span{' . $venue_styles . '}';
	}
	$raed_more_key = 'ectbe_read_more_typography';
	if ( isset( $settings[ $raed_more_key . $typo_index ] ) &&
		 $settings[ $raed_more_key . $typo_index ] == 'custom' ) {
		$read_more_style = get_typography_settings( $raed_more_key, $settings );
		$custom_styles  .= $widgetID . ' .ectbe-evt-read-more{' . $read_more_style . '}';
	}
	if ( ! empty( $custom_styles ) ) {
		return $custom_styles;

	} else {
		return false;
	}
}
// get an older version style settings
function get_typography_settings( $key, $all_settings ) {
	$fields    = array(
		'font_family',
		'font_size',
		'font_weight',
		'text_transform',
		'font_style',
		'text_decoration',
		'line_height',
		'letter_spacing',
		'word_spacing',
	);
	$field_css = '';
	foreach ( $fields as $field ) {
		$index     = $key . '_' . $field;
		$attribute = str_replace( '_', '-', $field );
		if ( isset( $all_settings[ $index ] ) && $all_settings[ $index ] !== '' ) {
			if ( is_array( $all_settings[ $index ] ) ) {
				if ( $all_settings[ $index ]['size'] !== '' ) {
					$unit       = $all_settings[ $index ]['unit'];
					$size       = $all_settings[ $index ]['size'];
					$field_css .= $attribute . ':' . $size . $unit . ';';
				}
			} else {
				$field_css .= $attribute . ':' . $all_settings[ $index ] . ';';
			}
		}
	}
	return $field_css;
}

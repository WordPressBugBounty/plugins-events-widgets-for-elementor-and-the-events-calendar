<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( $style == 'style-2' || $layout == 'minimal-list' ) {
	$ectbe_events_html .= '<div id="ectbe-date" class="ectbe-date-area">
						<span class="ectbe-ev-mo">' . esc_html( $ev_month ) . '</span>
						<span class="ectbe-ev-day">' . esc_html( $ev_day ) . '</span>
					</div>';
} else {
	$ectbe_events_html .= wp_kses_post( $event_schedule );
}
	$ectbe_events_html .= '<div class="ectbe-content-box">';
if ( $style == 'style-2' ) {
	$ectbe_events_html .= wp_kses_post( $event_schedule );
}
if ( $layout == 'minimal-list' ) {
	$ectbe_events_html .= $ev_time;
}
if ( $style == 'style-1' || $style == 'style-2' ) {
	$ectbe_events_html .= wp_kses_post( $ectbe_cate );
}
	$ectbe_events_html .= wp_kses_post( $evt_title );
if ( $layout != 'minimal-list' && $style != 'style-2' ) {
	$ectbe_events_html .= $ev_time;
}
if ( $layout != 'minimal-list' ) {
	$ectbe_events_html .= wp_kses_post( $venue_details_html );
	$ectbe_events_html .= wp_kses_post( $evt_desc );
	$ectbe_events_html .= $ectbe_cost;
}
if ( $style == 'style-2' || $layout == 'minimal-list' ) {
	$ectbe_events_html .= wp_kses_post( $ectbe_read_more );
}
	$ectbe_events_html .= '</div>';
if ( $layout != 'minimal-list' ) {
	if ( $style == 'style-2' ) {
		$ectbe_events_html .= wp_kses_post( $ev_post_img );
	} elseif ( $style == 'style-1' ) {
		$ectbe_events_html .= wp_kses_post( $ectbe_read_more );
	}
}



<?php
$calendar_html = '';
$calendar_html .= '<div class="ectbe-calendar-wrapper">';
$calendar_html .= '<div class="ectbe_calendar_events_spinner"><img src="'.esc_url( ECTBE_URL ) .'assets/images/ectbe-preloader.gif"></div>';
	 
$calendar_html .= '<div id="ectbe-event-calendar-'.esc_attr( $this->get_id() ).'" class="ectbe-event-calendar-cls"
    data-cal_id = "'.esc_attr($this->get_id()).'"
    data-locale = "'.esc_attr( $local ).'"
    data-defaultview = "'.esc_attr( $default_view ).'"
    data-first_day="'.esc_attr( $settings['ectbe_calendar_first_day'] ).'"
    data-daterange = "'.esc_attr( $daterange ).'"
    data-rangestart = "'.esc_attr( $rangeStart).'"
    data-rangeend = "'.esc_attr( $rangeEnd ).'"
    data-max_events = "'.esc_attr( $max_events ).'"
    data-ev_category="'.esc_attr( htmlspecialchars(json_encode($ev_category), ENT_QUOTES, 'UTF-8' )).'"
    data-textcolor = "'.esc_attr( $textColor ).'"
    data-color = "'.esc_attr( $color ).'">';
    $calendar_html .= '</div>';


    $calendar_html .=  '<div id="ectbe-popup-wraper" class="ectbe-modal ectbe-zoom-in">
            <div class="ectbe-ec-modal-bg"></div>
            <div class="ectbe-modal-content">
                <div class="ectbe-featured-img"></div>
                <div class="ectbe-modal-header">                    
                    <div class="ectbe-modal-close"><span>X</span></div>
                    <h2 class="ectbe-ec-modal-title"></h2>
                    <span class="ectbe-event-date-start ectbe-event-popup-date"></span>
                    <span class="ectbe-event-date-end ectbe-event-popup-date"></span>
                </div>
                <div class="ectbe-modal-body">
                    <span class="ectbe-cost"></span>
                    <p></p>
                </div>';
                if($details_link!='yes'){
                    $calendar_html .='<div class="ectbe-modal-footer">
                        <a class="ectbe-event-details-link"><button>'.esc_html__("Read More","ectbe").'</button></a>
                    </div>';
                }
                
                $calendar_html .='</div>
        </div>';
  

$calendar_html .='</div>';

echo wp_kses_post($calendar_html);
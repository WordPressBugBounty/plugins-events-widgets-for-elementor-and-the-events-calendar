<?php
if( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use Elementor\Widget_Base;
use Elementor\Utils;
use Elementor\Repeater;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Typography;
class ECTBE_Widget extends \Elementor\Widget_Base {
	public function __construct( $data = array(), $args = null ) {
		parent::__construct( $data, $args );
			add_action( 'elementor/editor/after_save', array( $this, 'ectbe_update_migration_status' ), 10, 2 );
			wp_register_style( 'ectbe-calendar-main-css', ECTBE_URL . 'assets/lib/css/calendar-main.min.css', null, ECTBE_VERSION, 'all' );
			wp_register_style( 'ectbe-custom-css', ECTBE_URL . 'assets/css/custom-styles.min.css', null, ECTBE_VERSION, 'all' );
			wp_register_style( 'ectbe-list-css', ECTBE_URL . 'assets/css/ectbe-list.min.css', null, ECTBE_VERSION, 'all' );
			wp_register_style( 'ectbe-common-styles', ECTBE_URL . 'assets/css/ectbe-common-styles.min.css', null, ECTBE_VERSION, 'all' );
			add_action( 'elementor/frontend/after_enqueue_scripts', array( $this, 'ectbe_enqueue_all_calendar_scripts' ) );
	}
	/**
	 * update some settings when user saves Elementor data.
	 *
	 * @since 1.0.0
	 * @param int   $post_id     The ID of the post.
	 * @param array $editor_data The editor data.
	 */
	function ectbe_update_migration_status( $post_id, $editor_data ) {
		if ( get_post_meta( $post_id, 'ectbe_exists', true ) ) {
			if ( current_user_can( 'edit_post', $post_id ) ) {
				update_post_meta( $post_id, 'ectbe_style_migration', 'done' );
				update_option( 'ectbe-migration-status', 'done' );
			}
			return;
		}
	}

	/**
	 * Function to register all the scripts with elementor frontend.
	 *
	 * @since 1.0.0
	 */
	public function ectbe_enqueue_all_calendar_scripts(){
		wp_register_script( 'ectbe-calendar-main', ECTBE_URL . 'assets/lib/js/calendar-main.min.js', array( 'elementor-frontend' ), ECTBE_VERSION, true );
		wp_register_script( 'ectbe-calendar-locales', ECTBE_URL . 'assets/lib/js/calendar-locales-all.min.js', array( 'elementor-frontend' ), ECTBE_VERSION, true );
		wp_register_script( 'ectbe-calendar-js', ECTBE_URL . 'assets/js/calendar.js', array( 'elementor-frontend', 'wp-api-request', 'moment' ), ECTBE_VERSION, true );
		wp_localize_script( 'ectbe-calendar-js', 'ectbe_callback_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}

	public function get_script_depends() {
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
			return array( 'ectbe-calendar-main', 'ectbe-calendar-locales', 'ectbe-calendar-js' );
		}
		$settings = $this->get_settings_for_display();
		$layout   = $settings['ectbe_layout'];
		$scripts  = array();
		if ( $layout == 'calendar' ) {
			array_push( $scripts, 'ectbe-calendar-main', 'ectbe-calendar-locales', 'ectbe-calendar-js' );
		}
		return $scripts;
	}
	public function get_style_depends() {
		return array( 'ectbe-calendar-main-css', 'ectbe-custom-css', 'ectbe-list-css', 'ectbe-minimal-list', 'ectbe-common-styles' );
	}
	public function get_name() {
		return 'the-events-calendar-addon';
	}
	public function get_title() {
		return __( 'Events Widgets', 'events-widgets-for-elementor-and-the-events-calendar' );
	}
	public function get_icon() {
		return 'ectbe-eicons-logo';
	}
	public function get_categories() {
		return array( 'general' );
	}
	protected function register_controls() {
		$this->start_controls_section(
			'ectbe_the_events_calendar_addon',
			array(
				'label' => __( 'Events Widgets Settings', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);
		$this->add_control(
			'ectbe_type',
			array(
				'label'     => __( 'Type of Events', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'future',
				'options'   => array(
					'future' => __( 'Upcoming Events', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'past'   => __( 'Past Events', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'all'    => __( 'All (Upcoming + Past)', 'events-widgets-for-elementor-and-the-events-calendar' ),
				),
				'condition' => array(
					'ectbe_layout!' => 'calendar',
				),
			)
		);
		$this->add_control(
			'ectbe_ev_category',
			array(
				'label'       => __( 'Event Category', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'default'     => array( 'all' ),
				'options'     => ectbe_get_tags(
					array(
						'taxonomy'   => 'tribe_events_cat',
						'hide_empty' => false,
					)
				),
			)
		);
		$this->add_control(
			'ectbe_event_source',
			array(
				'label'       => __( 'Events Time', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'        => Controls_Manager::SELECT,
				'label_block' => true,
				'default'     => 'all',
				'options'     => array(
					'all'        => __( 'All Events', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'date_range' => __( 'Events in between Date Range', 'events-widgets-for-elementor-and-the-events-calendar' ),
				),
				// 'render_type' => 'none',
			)
		);
		$this->add_control(
			'ectbe_date_range_start',
			array(
				'label'       => __( 'Start Date', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'        => Controls_Manager::DATE_TIME,
				'default'     => gmdate( 'Y-m-d H:i', current_time( 'timestamp', 0 ) ),
				'condition'   => array(
					'ectbe_event_source' => 'date_range',
				),
				'description' => __( 'Start date of date range', 'events-widgets-for-elementor-and-the-events-calendar' ),
			)
		);
		$this->add_control(
			'ectbe_date_range_end',
			array(
				'label'       => __( 'End Date', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'        => Controls_Manager::DATE_TIME,
				'default'     => gmdate( 'Y-m-d H:i', strtotime( '+6 months', current_time( 'timestamp', 0 ) ) ),
				'condition'   => array(
					'ectbe_event_source' => 'date_range',
				),
				'description' => __( 'End Date of Date Range', 'events-widgets-for-elementor-and-the-events-calendar' ),
			)
		);
		$this->add_control(
			'ectbe_layout',
			array(
				'label'       => __( 'Layout', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'        => Controls_Manager::SELECT,
				'label_block' => true,
				'default'     => 'list',
				'options'     => array(
					'list'         => __( 'List', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'minimal-list' => __( 'Minimal List', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'calendar'     => __( 'Calendar', 'events-widgets-for-elementor-and-the-events-calendar' ),
				),
				'description' => __(
					'<a class="like_it_btn button button-primary" target="_blank"
				href="https://eventscalendaraddons.com/plugin/events-widgets-pro/?utm_source=ectbe_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=layout_settings">
				Get Pro ⇗</a> for more advance layouts ',
					'events-widgets-for-elementor-and-the-events-calendar'
				),
			)
		);
		$this->add_control(
			'ectbe_styles',
			array(
				'label'     => __( 'Select Style', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => array(
					'style-1' => __( 'Style 1', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'style-2' => __( 'Style 2', 'events-widgets-for-elementor-and-the-events-calendar' ),
				),
				'default'   => 'style-1',
				'condition' => array(
					'ectbe_layout!' => array(
						'calendar',
						'minimal-list',
					),
				),
			)
		);
		$this->add_control(
			'ectbe_max_events',
			array(
				'label'       => __( 'Number of Events', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'default'     => 25,
				'description' => __( 'Maximum number of events to display', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'conditions'  => array(
					'relation' => 'or',
					'terms'    => array(
						array(
							'name'     => 'ectbe_layout',
							'operator' => '!==',
							'value'    => 'calendar',
						),
						array(
							'relation' => 'and',
							'terms'    => array(
								array(
									'name'     => 'ectbe_layout',
									'operator' => '===',
									'value'    => 'calendar',
								),
								array(
									'name'     => 'ectbe_event_source',
									'operator' => '===',
									'value'    => 'date_range',
								),
							),
						),
					),
				),
			)
		);
		$this->add_control(
			'ectbe_calendar_default_view',
			array(
				'label'     => __( 'Default View', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => array(
					'dayGridMonth' => __( 'Month', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'timeGridDay'  => __( 'Day', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'timeGridWeek' => __( 'Week', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'listMonth'    => __( 'List', 'events-widgets-for-elementor-and-the-events-calendar' ),
				),
				'default'   => 'dayGridMonth',
				'condition' => array(
					'ectbe_layout' => 'calendar',
				),
			)
		);
		$this->add_control(
			'ectbe_calendar_first_day',
			array(
				'label'     => __( 'First Day of Week', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => array(
					'0' => __( 'Sunday', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'1' => __( 'Monday', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'2' => __( 'Tuesday', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'3' => __( 'Wednesday', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'4' => __( 'Thursday', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'5' => __( 'Friday', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'6' => __( 'Saturday', 'events-widgets-for-elementor-and-the-events-calendar' ),
				),
				'default'   => '0',
				'condition' => array(
					'ectbe_layout' => 'calendar',
				),
			)
		);
		$this->add_control(
			'ectbe_hide_read_more_link',
			array(
				'label'        => __( 'Hide Read More Link', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_block'  => false,
				'return_value' => 'yes',
				'description'  => __( 'Hide Read More link in event popup', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'condition'    => array(
					'ectbe_layout' => 'calendar',
				),
			)
		);
		$this->add_control(
			'ectbe_calendar_bg_color',
			array(
				'label'       => __( 'Background Color', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => '#5725ff',
				'description' => __( 'Background Color of Multidays Event', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'condition'   => array(
					'ectbe_layout' => 'calendar',
				),
			)
		);
		$this->add_control(
			'ectbe_calendar_text_color',
			array(
				'label'     => __( 'Text Color', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'condition' => array(
					'ectbe_layout' => 'calendar',
				),
			)
		);
		$this->add_control(
			'ectbe_date_formats',
			array(
				'label'     => __( 'Date Format', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'default',
				'options'   => array(
					'default'   => __( 'Default (01 January 2019)', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'start_end' => __( '01 January 2019 - 03 January 2019', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'MD,Y'      => __( 'Md,Y (Jan 01, 2019)', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'FD,Y'      => __( 'Fd,Y (January 01, 2019)', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'DM'        => __( 'dM (01 Jan)', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'DML'       => __( 'dMl (01 Jan Monday)', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'DF'        => __( 'dF (01 January)', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'MD'        => __( 'Md (Jan 01)', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'MD,YT'     => __( 'Md,YT (Jan 01, 2019 8:00am-5:00pm)', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'full'      => __( 'Full (01 January 2019 8:00am-5:00pm)', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'jMl'       => __( 'jMl (01 Jan Monday)', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'd.FY'      => __( 'd.FY (01. January 2019)', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'd.F'       => __( 'd.F (01. January)', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'ldF'       => __( 'ldF (Monday 01 January)', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'Mdl'       => __( 'Mdl (Jan 01 Monday)', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'd.Ml'      => __( 'd.Ml (01. Jan Monday)', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'dFT'       => __( 'dFT (01 January 8:00am-5:00pm)', 'events-widgets-for-elementor-and-the-events-calendar' ),
				),
				'condition' => array(
					'ectbe_layout!' => array(
						'calendar',
						'minimal-list',
					),
				),
			)
		);
		$this->add_control(
			'ectbe_order',
			array(
				'label'     => __( 'Events Order', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'ASC',
				'options'   => array(
					'ASC'  => __( 'ASC', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'DESC' => __( 'DESC', 'events-widgets-for-elementor-and-the-events-calendar' ),
				),
				'condition' => array(
					'ectbe_layout!' => 'calendar',
				),
			)
		);
		$this->add_control(
			'ectbe_venue',
			array(
				'label'     => __( 'Hide Venue', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'no',
				'options'   => array(
					'no'  => __( 'NO', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'yes' => __( 'Yes', 'events-widgets-for-elementor-and-the-events-calendar' ),
				),
				'condition' => array(
					'ectbe_layout!' => array(
						'calendar',
						'minimal-list',
					),
				),
			)
		);
		$this->add_control(
			'ectbe_display_desc',
			array(
				'label'     => __( 'Display Description', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'yes',
				'options'   => array(
					'yes' => __( 'Yes', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'no'  => __( 'NO', 'events-widgets-for-elementor-and-the-events-calendar' ),
				),
				'condition' => array(
					'ectbe_layout!' => array(
						'calendar',
						'minimal-list',
					),
				),
			)
		);
		$this->add_control(
			'ectbe_display_cate',
			array(
				'label'     => __( 'Display Categoery', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'yes',
				'options'   => array(
					'yes' => __( 'Yes', 'events-widgets-for-elementor-and-the-events-calendar' ),
					'no'  => __( 'NO', 'events-widgets-for-elementor-and-the-events-calendar' ),
				),
				'condition' => array(
					'ectbe_layout!' => array(
						'calendar',
						'minimal-list',
					),
				),
			)
		);

		$this->add_control(
			'ectbe_disable_schema',
			array(
				'label'     => __( 'Disable Schema Markup', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_block'  => false,
				'return_value' => 'yes',
				'condition!'    => array(
					'ectbe_layout' => 'calendar',
				),
			)
		);
		$this->add_control(
			'ectbe_pro_features_1',
			array(
				'label'           => '',

				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => '<button class="ectbe-pro-features-view-demo">
							<a href="' . esc_url( 'https://eventscalendaraddons.com/demos/events-widgets-pro/?utm_source=ectbe_plugin&utm_medium=inside&utm_campaign=demo&utm_content=editor_panel' ) . '" target="_blank">' . esc_html__( 'View Demo', 'events-widgets-for-elementor-and-the-events-calendar' ) . '</a>
							</button> <button class="ectbe-pro-features-get-pro">
							<a href="' . esc_url( 'https://eventscalendaraddons.com/plugin/events-widgets-pro/?utm_source=ectbe_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=editor_panel' ) . '" target="_blank">' . esc_html__( 'Get Pro ⇗', 'events-widgets-for-elementor-and-the-events-calendar' ) . '</a> 
							</button>',
				'content_classes' => 'ectbe-pro-features-list',
			)
		);
		$this->add_control(
			'ectbe_pro_features_2',
			array(
				'label'           => '',
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => '<div class="ectbe-review-list" style=" line-height: 1.5em; background: #FFD5E6; color: #151213; padding: 15px; ">
				Thanks for using <strong>The Events Calendar Widget for Elementor</strong>! If you have a moment, could you kindly leave us a review? We\'d greatly appreciate it and it helps us improve our product. <br><br>Thanks in advance! <br><br>
							<strong><a href="https://wordpress.org/support/plugin/events-widgets-for-elementor-and-the-events-calendar/reviews/#new-post" target="_blank" style=" border: 2px solid; padding: 6px; ">Share Review ⭐⭐⭐⭐⭐</a></strong><br/>
							</div>',
				'content_classes' => 'ectbe-review-list',
			)
		);
		$this->end_controls_section();
		// style section started
		$this->start_controls_section(
			'ectbe_style_section',
			array(
				'label'     => __( 'Color & Typography Settings', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'ectbe_layout!' => 'calendar',
				),
			)
		);
		$this->add_control(
			'ectbe_main_skin_section',
			array(
				'label'     => __( 'Main Skin', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'condition' => array(
					'ectbe_layout!' => 'minimal-list',
				),
			)
		);
		$this->add_control(
			'ectbe_main_skin_color',
			array(
				'label'     => __( 'Color', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array(
					'ectbe_layout!' => 'minimal-list',
				),
				'selectors' => array(
					'{{WRAPPER}} .ectbe-wrapper' => '--e-ectbe-date-area-background: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'ectbe_featured_skin_section',
			array(
				'label'     => __( 'Featured Event', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_control(
			'ectbe_featured_skin_color',
			array(
				'label'     => __( 'Skin Color', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ectbe-wrapper' => '--ectbe-featd-evt-bg-color: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'ectbe_featured_font_color',
			array(
				'label'     => __( 'Font Color', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ectbe-wrapper' => '--ectbe-featd-evt-color: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'ectbe_bg_color_section',
			array(
				'label'     => __( 'Event Background ', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array(
					'ectbe_layout!' => 'minimal-list',
					'ectbe_styles!' => 'style-2',
				),
			)
		);
		$this->add_control(
			'ectbe_event_bgcolor',
			array(
				'label'     => __( 'Color', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array(
					'ectbe_layout!' => 'minimal-list',
					'ectbe_styles!' => 'style-2',
				),
				'selectors' => array(
					'{{WRAPPER}} .ectbe-wrapper' => '--e-ectbe-content-box-background:{{VALUE}}',
				),
			)
		);
		$this->add_control(
			'ectbe_date_section',
			array(
				'label'     => __( 'Event Date', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_control(
			'ectbe_date_color',
			array(
				'label'     => __( 'Color', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ectbe-wrapper' => '--e-ectbe-date-area-color: {{VALUE}}',
				),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ectbe_date_typography',
				'label'    => __( 'Typography', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'selector' =>
					'{{WRAPPER}} .ectbe-list-wrapper.style-1 .ectbe-date-area,
					{{WRAPPER}} .ectbe-content-box .ectbe-date-area span,
					{{WRAPPER}} .ectbe-minimal-list-wrapper .ectbe-evt-time',
			)
		);
		/*---- Date / Custom Label ----*/
		$this->add_control(
			'ectbe_title_section',
			array(
				'label'     => __( 'Event Title ', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_control(
			'ectbe_title_color',
			array(
				'label'     => __( 'Color', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ectbe-wrapper' => '--e-ectbe-evt-title-color: {{VALUE}}',
				),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'               => 'ectbe_title_typography',
				'label'              => __( 'Typography', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'selector'           => '{{WRAPPER}} .ectbe-evt-title .ectbe-evt-url',
				'frontend_available' => true,
			)
		);
		$this->add_control(
			'ectbe_desc_section',
			array(
				'label'     => __( 'Event Description', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array(
					'ectbe_layout!' => 'minimal-list',
				),
			)
		);
		$this->add_control(
			'ectbe_desc_color',
			array(
				'label'     => __( 'Color', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array(
					'ectbe_layout!' => 'minimal-list',
				),
				'selectors' => array(
					'{{WRAPPER}} .ectbe-wrapper' => '--e-ectbe-evt-description-color: {{VALUE}}',
				),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'ectbe_desc_typography',
				'label'     => __( 'Typography', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'condition' => array(
					'ectbe_layout!' => 'minimal-list',
				),
				'selector'  => '{{WRAPPER}} .ectbe-evt-description',
				'separator' => 'before',
			)
		);
		$this->add_control(
			'ectbe_venue_section',
			array(
				'label'     => __( 'Event Venue', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array(
					'ectbe_layout!' => 'minimal-list',
				),
			)
		);
		$this->add_control(
			'ectbe_venue_color',
			array(
				'label'     => __( 'Color', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array(
					'ectbe_layout!' => 'minimal-list',
				),
				'selectors' => array(
					'{{WRAPPER}} .ectbe-wrapper' => '--e-ectbe-evt-venue-color: {{VALUE}}',
				),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'ectbe_venue_typography',
				'label'     => __( 'Typography', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'condition' => array(
					'ectbe_layout!' => 'minimal-list',
				),
				'selector'  => '{{WRAPPER}} .ectbe-evt-venue span',
				'separator' => 'before',
			)
		);
		$this->add_control(
			'ectbe_read_more_section',
			array(
				'label'     => __( 'Find Out More', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_control(
			'ectbe_read_more_color',
			array(
				'label'     => __( 'Color', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ectbe-wrapper' => '--e-ectbe-evt-read-more-color: {{VALUE}}',
				),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'ectbe_read_more_typography',
				'label'    => __( 'Typography', 'events-widgets-for-elementor-and-the-events-calendar' ),
				'selector' => '{{WRAPPER}} .ectbe-evt-read-more',
			)
		);
		$this->add_control(
			'ectbe_get_pro_styles',
			array(
				'label'           => '',
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => '<button class="ectbe-pro-features">
							<a href="' . esc_url( 'https://eventscalendaraddons.com/plugin/events-widgets-pro/?utm_source=ectbe_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=style_settings' ) . '" target="_blank">' . esc_html__( 'For Advanced Styles - Get Pro ⇗', 'events-widgets-for-elementor-and-the-events-calendar' ) . '</a> 
							</button>',
				'content_classes' => 'ectbe-pro-features-list',
				'separator'       => 'before',
			)
		);
		$this->end_controls_section();

	}
	// for frontend
	protected function render() {
		$settings             = $this->get_settings_for_display();
		$layout               = $settings['ectbe_layout'];
		$fetchevnts           = $settings['ectbe_event_source'];
		$rangeStart           = $settings['ectbe_date_range_start'];
		$rangeEnd             = $settings['ectbe_date_range_end'];
		$max_events           = $settings['ectbe_max_events'];
		$ev_category          = $settings['ectbe_ev_category'];
		$local                = get_bloginfo( 'language' );
		$default_view         = $settings['ectbe_calendar_default_view'];
		$details_link         = $settings['ectbe_hide_read_more_link'];
		$textColor            = $settings['ectbe_calendar_text_color'];
		$color                = $settings['ectbe_calendar_bg_color'];
		$ectbe_venue          = $settings['ectbe_venue'];
		$daterange            = '';
		$compatibility_styles = '';
		global $post;
		$post_id   = $post->ID;
		$widget_id = $this->get_id();
		if ( ! get_post_meta( $post_id, 'ectbe_style_migration', true ) ) {
			update_post_meta( $post_id, 'ectbe_exists', 'yes' );
			$compatibility_styles .= ectbe_older_v_compatibility( $post_id, $settings, $layout, $widget_id );
		}
		if ( $fetchevnts == 'date_range' ) {
			$daterange = 'yes';
		}
		$ectbe_events_html  = '';
		$event_output = '';
		$ectbe_cost   = '';
		$evt_desc     = '';
		$display_cate = $settings['ectbe_display_cate'];
		$display_desc = $settings['ectbe_display_desc'];
		$display_year = '';
		$style        = isset( $settings['ectbe_styles'] ) ? $settings['ectbe_styles'] : 'style-1';
		$this->add_render_attribute(
			'ectbe-wrapper',
			array(
				'id'    => 'ectbe-wrapper-' . $widget_id,
				'class' => array( 'ectbe-wrapper', 'ectbe-' . $layout . '-wrapper', $style ),
			)
		);
		if ( $layout == 'calendar' ) {
			require ECTBE_PATH . 'widgets/layouts/ectbe-calendar.php';
		} else {
				$all_events = ectbe_get_the_events_calendar_events( $settings );
				global $post;
				$event_output .= '<!=========Events ' . $layout . ' Template ' . ECTBE_VERSION . '=========>';
				if(isset($settings['ectbe_disable_schema']) && $settings['ectbe_disable_schema'] !== 'yes'){
					if ( $all_events && class_exists( 'Tribe__Events__JSON_LD__Event' ) ) {
						$args    = array(
							'post_type'      => 'tribe_events',	
							'posts_per_page' => -1,
							'order'          => 'ASC',
						);
						$events = new WP_Query($args);
						$event_output .= Tribe__Events__JSON_LD__Event::instance()->get_markup( $events->posts);
					}
				}
				$event_output .= '<div ' . $this->get_render_attribute_string( 'ectbe-wrapper' ) . '>';
			if ( ! empty( $all_events ) ) {
				foreach ( $all_events as $event ) {
					$event_id           = $event['id'];
					$event_title        = $event['title'];
					$event_schedule     = $event['event_schedule'];
					$ev_time            = $event['ev_time'];
					$url                = $event['url'];
					$allDay             = $event['allDay'];
					$description        = $event['description'];
					$eventimgurl        = $event['eventimgurl'];
					$eventcost          = $event['eventcost'];
					$template           = '';
					$date_format        = '';
					$ectbe_cost         = '';
					$evt_title          = '';
					$ectbe_cate         = '';
					$header_html        = '';
					$cate               = ectbe_display_category( $event_id );
					$venue_details_html = '';
					$event_type         = tribe( 'tec.featured_events' )->is_featured( $event_id ) ? 'ectbe-featured-event' : 'ectbe-simple-event';
					$ev_post_img        = ectbe_get_event_image( $event_id, $size = 'large' );
					if ( ! empty( $ev_post_img ) ) {
						$ev_post_img_url = esc_url( $ev_post_img );
						$ev_post_img_html = '<div class="ectbe-evt-img"><img src="' . $ev_post_img_url . '" alt="" /></div>';
						$ev_post_img = wp_kses( $ev_post_img_html, [
							'div' => [ 'class' => [] ],
							'img' => [
								'src' => [],
								'alt' => [],
								'width' => [],
								'height' => [],
								'class' => [],
							],
						] );
					}
					$evt_title = '<div class="ectbe-evt-title"><a class="ectbe-evt-url" href="' . esc_url( $url ) . '">' . wp_kses_post( $event_title ) . '</a></div>';
					if ( ! empty( $cate && $display_cate == 'yes' ) ) {
						$ectbe_cate = '<div class="ectbe-ev-cate">' . wp_kses_post( $cate ) . '</div>';
					}
					if ( tribe_get_cost( $event_id ) ) {
						$ectbe_cost = '<div class="ectbe-evt-cost">
							<span class="ectbe-icon"><i class="ectbe-icon-ticket" aria-hidden="true"></i></span>
							<span class="ectbe-rate">' . wp_kses_post( tribe_get_cost( $event_id, true ) ) . '</span>
						</div>';
					}
					// Address
					$venue_details = tribe_get_venue_details( $event_id );
					if ( tribe_has_venue( $event_id ) && isset( $venue_details['linked_name'] ) && $ectbe_venue != 'yes' ) {
						$venue_details_html = '<div class="ectbe-evt-venue"><span class="ectbe-icon"><i class="ectbe-icon-location" aria-hidden="true"></i></span>
						<span class="ectbe-venue-details ectbe-address">' . implode( ',<br>', preg_replace( '#<a.*?>([^>]*)</a>#i', '$1', $venue_details ) ) . '</span></div>';
					}
					if ( $display_desc == 'yes' ) {
						$evt_desc = '<div class="ectbe-evt-description">' . tribe_events_get_the_excerpt( $event_id, wp_kses_allowed_html( 'post' ) ) . '</div>';
					}
					$ectbe_read_more = '<div class="ectbe-evt-more-box"><a class="ectbe-evt-read-more" href="' . esc_url( $url ) . '">' . esc_html__( 'Find out more', 'events-widgets-for-elementor-and-the-events-calendar' ) . '</a></div>';
					$ev_time         = '<div class="ectbe-evt-time"><i class="ectbe-icon-clock"></i>' . esc_html( $ev_time ) . '</div>';
					$ev_day          = tribe_get_start_date( $event_id, false, 'd' );
					$ev_month        = tribe_get_start_date( $event_id, false, 'M' );
					$event_year      = tribe_get_start_date( $event_id, true, 'F Y' );
					$ev_endday       = tribe_get_end_date( $event_id, false, 'd' );
					$ev_endmonth     = tribe_get_end_date( $event_id, false, 'M' );
					$ev_endyear      = tribe_get_end_date( $event_id, false, 'Y' );
					if ( ( $layout == 'list' && $style == 'style-2' ) && $event_year != $display_year ) {
						$display_year = $event_year;
						$ectbe_events_html .= '<div class="ectbe-month-header ' . esc_attr( $event_type ) . '">' . esc_attr( $display_year ) . '</div>';
					}
					$ectbe_events_html .= '<div id="event-' . esc_attr( $event_id ) . '" class="ectbe-inner-wrapper ' . esc_attr( $event_type ) . '">';

					require ECTBE_PATH . 'widgets/layouts/ectbe-list.php';
					$ectbe_events_html .= '</div>';
				}
			} else {
				$event_output .= '<h3>' . esc_html__( 'There is no Event', 'events-widgets-for-elementor-and-the-events-calendar' ) . '</h3>';
			}
			$event_output .= $ectbe_events_html;
			$event_output .= '</div>';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $event_output;
			if ( $compatibility_styles != '' ) {
				echo '<style type="text/css">' . esc_attr( wp_strip_all_tags( $compatibility_styles ) ) . '</style>';
			}
		}
	}
}
\Elementor\Plugin::instance()->widgets_manager->register( new ECTBE_Widget() );


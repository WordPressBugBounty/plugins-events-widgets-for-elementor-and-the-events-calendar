<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Add a custom category for panel widgets
add_action(
	'elementor/init',
	function() {
		\Elementor\Plugin::$instance->elements_manager->add_category(
			'ectbe',                 // the name of the category
			array(
				'title' => esc_html__( 'Events Calendar Addon For Elementor', 'ectbe' ),
				'icon'  => 'fa fa-header', // default icon
			),
			1 // position
		);
	}
);

/**
 * Main Plugin Class
 *
 * Register new elementor widget.
 *
 * @since 1.0.0
 */
class ECTBE_WidgetClass {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function __construct() {
		$this->ectbe_add_actions();
	}

	/**
	 * Add Actions
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 */
	private function ectbe_add_actions() {
		add_action( 'elementor/widgets/register', array($this, 'ectbe_on_widgets_registered' ));		
		add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'ectbe_editor_styles' ] );
	}
	
	/**
	 * Enqueue Editor Styles
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function ectbe_editor_styles() {
		// Sanitize URL
		$style_url = esc_url( ECTBE_URL . 'assets/css/ectbe-editor.min.css' );
		wp_enqueue_style(
			'ectbe_editor_styles',
			$style_url,
			array()
		);
	}

	/**
	 * On Widgets Registered
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function ectbe_on_widgets_registered() {
		$this->ectbe_widget_includes();
	}

	/**
	 * Includes
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 */
	private function ectbe_widget_includes() {
		require_once ECTBE_PATH . 'widgets/ectbe-widget.php';
	}

}

new ECTBE_WidgetClass();

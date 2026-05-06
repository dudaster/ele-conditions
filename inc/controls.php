<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once ELECONDITIONS_DIR . 'inc/parse_conditions.php';

add_action( 'elementor/element/before_section_start', function( $element, $section_id, $args ) {
	if ( '_section_responsive' !== $section_id ) return;

	$element->start_controls_section(
		'conditional_section',
		[
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			'label' => __( 'Conditions', 'ele-conditions' ),
		]
	);

	$repeater = new \Elementor\Repeater();

	$repeater->add_control(
		'cond_var_preset',
		[
			'label'   => __( 'Variable', 'ele-conditions' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'options' => [
				''            => __( '— select —', 'ele-conditions' ),
				'ID'          => 'ID',
				'name'        => 'name',
				'post_excerpt'=> 'post_excerpt',
				'description' => 'description',
				'permalink'   => 'permalink',
				'content'     => 'content',
				'now'         => 'now',
				'custom'      => __( 'Custom...', 'ele-conditions' ),
			],
			'default' => '',
		]
	);

	$repeater->add_control(
		'cond_var_custom',
		[
			'label'       => __( 'Custom variable name', 'ele-conditions' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'placeholder' => __( 'e.g. my_field', 'ele-conditions' ),
			'label_block' => true,
			'condition'   => [ 'cond_var_preset' => 'custom' ],
		]
	);

	$repeater->add_control(
		'cond_operator',
		[
			'label'   => __( 'Operator', 'ele-conditions' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'options' => [
				'=='  => '== (equal)',
				'!='  => '!= (not equal)',
				'===' => '=== (strict equal)',
				'!==' => '!== (strict not equal)',
				'>'   => '> (greater than)',
				'<'   => '< (less than)',
				'>='  => '>= (greater or equal)',
				'<='  => '<= (less or equal)',
			],
			'default' => '==',
		]
	);

	$repeater->add_control(
		'cond_value',
		[
			'label'       => __( 'Value', 'ele-conditions' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'placeholder' => __( 'e.g. 5, true, null, published', 'ele-conditions' ),
			'label_block' => true,
		]
	);

	$repeater->add_control(
		'cond_logic',
		[
			'label'       => __( 'Connect next with', 'ele-conditions' ),
			'type'        => \Elementor\Controls_Manager::SELECT,
			'options'     => [
				'AND' => __( 'AND — all must match', 'ele-conditions' ),
				'OR'  => __( 'OR — any must match', 'ele-conditions' ),
			],
			'default'     => 'AND',
		]
	);

	$element->add_control(
		'conditions_list',
		[
			'label'       => __( 'Conditions', 'ele-conditions' ),
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'title_field' => '{{{ cond_var_preset === "custom" ? cond_var_custom : cond_var_preset }}} {{{ cond_operator }}} {{{ cond_value }}}',
		]
	);

	$element->add_control(
		'element_condition_debug',
		[
			'label'     => __( 'Debug mode', 'ele-conditions' ),
			'type'      => \Elementor\Controls_Manager::SWITCHER,
			'default'   => '',
			'label_on'  => __( 'On', 'ele-conditions' ),
			'label_off' => __( 'Off', 'ele-conditions' ),
		]
	);

	$element->end_controls_section();
}, 10, 3 );

add_action( 'elementor/widget/render_content', function( $content, $widget ) {
	$settings = $widget->get_active_settings();

	if ( empty( $settings['conditions_list'] ) ) return $content;

	$debug = ! empty( $settings['element_condition_debug'] );

	return elecond_evaluate_group( $settings['conditions_list'], $debug ) ? $content : '';
}, 10, 2 );

function elecond_hide_element( \Elementor\Element_Base $element ) {
	$settings = $element->get_active_settings();

	if ( empty( $settings['conditions_list'] ) ) return;

	$debug = ! empty( $settings['element_condition_debug'] );

	if ( elecond_evaluate_group( $settings['conditions_list'], $debug ) ) return;

	$style = $debug && ( current_user_can( 'editor' ) || current_user_can( 'administrator' ) )
		? 'opacity:0.5; border: 3px solid red;'
		: 'display:none;';

	$element->add_render_attribute( '_wrapper', [ 'style' => $style ] );
}

add_action( 'elementor/frontend/widget/before_render',  'elecond_hide_element' );
add_action( 'elementor/frontend/section/before_render', 'elecond_hide_element' );

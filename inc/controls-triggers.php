<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'elementor/element/before_section_start', function( $element, $section_id, $args ) {
	if ( '_section_responsive' !== $section_id ) return;

	$element->start_controls_section(
		'triggers_section',
		[
			'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
			'label' => __( 'Triggered', 'ele-conditions' ),
		]
	);

	$repeater = new \Elementor\Repeater();

	// ── Trigger ─────────────────────────────────────────────
	$repeater->add_control(
		'trigger_type',
		[
			'label'   => __( 'Trigger', 'ele-conditions' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'options' => [
				'click' => __( 'Click', 'ele-conditions' ),
				'hover' => __( 'Hover (mouse enter)', 'ele-conditions' ),
				'delay' => __( 'Delay on load', 'ele-conditions' ),
			],
			'default' => 'click',
		]
	);

	$repeater->add_control(
		'trigger_delay_ms',
		[
			'label'       => __( 'Delay (ms)', 'ele-conditions' ),
			'type'        => \Elementor\Controls_Manager::NUMBER,
			'default'     => 1000,
			'min'         => 0,
			'step'        => 100,
			'description' => __( 'Milliseconds after page load before action fires.', 'ele-conditions' ),
			'condition'   => [ 'trigger_type' => 'delay' ],
		]
	);

	// ── Action ──────────────────────────────────────────────
	$repeater->add_control(
		'action_type',
		[
			'label'     => __( 'Action', 'ele-conditions' ),
			'type'      => \Elementor\Controls_Manager::SELECT,
			'options'   => [
				'show'          => __( 'Show', 'ele-conditions' ),
				'hide'          => __( 'Hide', 'ele-conditions' ),
				'toggle'        => __( 'Toggle', 'ele-conditions' ),
				'add_class'     => __( 'Add Class', 'ele-conditions' ),
				'remove_class'  => __( 'Remove Class', 'ele-conditions' ),
				'toggle_class'  => __( 'Toggle Class', 'ele-conditions' ),
				'scroll_to'     => __( 'Scroll To', 'ele-conditions' ),
				'focus'         => __( 'Focus', 'ele-conditions' ),
				'close_others'  => __( 'Close Others in Group', 'ele-conditions' ),
			],
			'default'   => 'toggle',
		]
	);

	// ── Target ──────────────────────────────────────────────
	$repeater->add_control(
		'action_target',
		[
			'label'       => __( 'Target (CSS selector)', 'ele-conditions' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'placeholder' => __( 'Empty = this element. e.g. #my-id, .my-class', 'ele-conditions' ),
			'label_block' => true,
			'condition'   => [
				'action_type!' => 'close_others',
			],
		]
	);

	// ── Class (for class actions) ────────────────────────────
	$repeater->add_control(
		'action_class',
		[
			'label'       => __( 'CSS Class', 'ele-conditions' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'placeholder' => __( 'e.g. is-active', 'ele-conditions' ),
			'label_block' => true,
			'condition'   => [
				'action_type' => [ 'add_class', 'remove_class', 'toggle_class' ],
			],
		]
	);

	// ── Group class (for close_others) ──────────────────────
	$repeater->add_control(
		'action_group_class',
		[
			'label'       => __( 'Group CSS Class', 'ele-conditions' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'placeholder' => __( 'e.g. accordion-item', 'ele-conditions' ),
			'description' => __( 'All elements sharing this class are the group. Others will be hidden.', 'ele-conditions' ),
			'label_block' => true,
			'condition'   => [ 'action_type' => 'close_others' ],
		]
	);

	$element->add_control(
		'triggers_list',
		[
			'label'       => __( 'Triggers & Actions', 'ele-conditions' ),
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'title_field' => '{{ trigger_type }} → {{ action_type }}',
		]
	);

	$element->end_controls_section();
}, 10, 3 );

// Attach triggers data to element wrapper on frontend render
function elecond_attach_triggers( \Elementor\Element_Base $element ) {
	$settings = $element->get_active_settings();
	if ( empty( $settings['triggers_list'] ) ) return;

	$element->add_render_attribute( '_wrapper', [
		'data-elecond-triggers' => wp_json_encode( $settings['triggers_list'] ),
	] );
}

add_action( 'elementor/frontend/widget/before_render',  'elecond_attach_triggers' );
add_action( 'elementor/frontend/section/before_render', 'elecond_attach_triggers' );

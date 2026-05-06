<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'elementor/element/before_section_start', function( $element, $section_id, $args ) {
	if ( '_section_responsive' !== $section_id ) return;

	$element->start_controls_section(
		'triggers_section',
		[
			'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
			'label' => __( 'Triggers', 'ele-conditions' ),
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
				'click'           => __( 'Click', 'ele-conditions' ),
				'hover'           => __( 'Hover (mouse enter)', 'ele-conditions' ),
				'delay'           => __( 'Delay on load', 'ele-conditions' ),
				'scroll_into_view'=> __( 'Scroll into view', 'ele-conditions' ),
				'time_on_page'    => __( 'Time on page', 'ele-conditions' ),
				'exit_intent'     => __( 'Exit intent', 'ele-conditions' ),
				'first_visit'     => __( 'First visit', 'ele-conditions' ),
				'nth_visit'       => __( 'Nth visit', 'ele-conditions' ),
				'ab_group_a'      => __( 'A/B — Group A', 'ele-conditions' ),
				'ab_group_b'      => __( 'A/B — Group B', 'ele-conditions' ),
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

	$repeater->add_control(
		'trigger_time_seconds',
		[
			'label'       => __( 'Seconds on page', 'ele-conditions' ),
			'type'        => \Elementor\Controls_Manager::NUMBER,
			'default'     => 10,
			'min'         => 1,
			'step'        => 1,
			'description' => __( 'Action fires after the user has spent this many seconds on the page.', 'ele-conditions' ),
			'condition'   => [ 'trigger_type' => 'time_on_page' ],
		]
	);

	$repeater->add_control(
		'trigger_visit_count',
		[
			'label'       => __( 'Visit number', 'ele-conditions' ),
			'type'        => \Elementor\Controls_Manager::NUMBER,
			'default'     => 2,
			'min'         => 1,
			'step'        => 1,
			'description' => __( 'Action fires on exactly this page-view count (tracked per URL via localStorage).', 'ele-conditions' ),
			'condition'   => [ 'trigger_type' => 'nth_visit' ],
		]
	);

	$repeater->add_control(
		'trigger_ab_name',
		[
			'label'       => __( 'Test name', 'ele-conditions' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => 'default',
			'placeholder' => __( 'e.g. hero_banner', 'ele-conditions' ),
			'description' => __( 'Unique name for this A/B test. Group is assigned randomly and stored in localStorage.', 'ele-conditions' ),
			'label_block' => true,
			'condition'   => [ 'trigger_type' => [ 'ab_group_a', 'ab_group_b' ] ],
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

	// ── Hide initially ───────────────────────────────────────
	$element->add_control(
		'trigger_hide_initially',
		[
			'label'       => __( 'Hide initially', 'ele-conditions' ),
			'type'        => \Elementor\Controls_Manager::SWITCHER,
			'default'     => '',
			'label_on'    => __( 'Yes', 'ele-conditions' ),
			'label_off'   => __( 'No', 'ele-conditions' ),
			'description' => __( 'Hide element on load. Useful when a trigger should reveal it (e.g. Show after delay).', 'ele-conditions' ),
		]
	);

	$element->end_controls_section();
}, 10, 3 );

// Attach triggers data to element wrapper on frontend render
function elecond_attach_triggers( \Elementor\Element_Base $element ) {
	$settings = $element->get_active_settings();

	if ( ! empty( $settings['triggers_list'] ) ) {
		$encoded = wp_json_encode( $settings['triggers_list'] );
		if ( $encoded !== false ) {
			$element->add_render_attribute( '_wrapper', [
				'data-elecond-triggers' => $encoded,
			] );
		}
	}

	// Checked independently so hide_initially works even without triggers
	if ( ! empty( $settings['trigger_hide_initially'] ) ) {
		$element->add_render_attribute( '_wrapper', [
			'data-elecond-hide-initially' => '1',
		] );
	}
}

add_action( 'elementor/frontend/widget/before_render',  'elecond_attach_triggers' );
add_action( 'elementor/frontend/section/before_render', 'elecond_attach_triggers' );

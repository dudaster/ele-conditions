<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once ELECONDITIONS_DIR . 'inc/parse_conditions.php';

add_action( 'elementor/element/before_section_start', function( $element, $section_id, $args ) {
	if ( '_section_responsive' !== $section_id ) return;

	$element->start_controls_section(
		'conditional_section',
		[
			'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
			'label' => __( 'Ele Conditions', 'ele-conditions' ),
		]
	);

	$repeater = new \Elementor\Repeater();

	// ── Condition type ──────────────────────────────────────
	$repeater->add_control(
		'cond_type',
		[
			'label'   => __( 'Type', 'ele-conditions' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'options' => [
				'simple'        => __( 'Variable comparison', 'ele-conditions' ),
				'time_interval' => __( 'Time interval (daily, HH:MM)', 'ele-conditions' ),
				'date_interval' => __( 'Date/time interval (YYYY-MM-DD)', 'ele-conditions' ),
			],
			'default' => 'simple',
		]
	);

	// ── Simple: variable ────────────────────────────────────
	$repeater->add_control(
		'cond_var_preset',
		[
			'label'     => __( 'Variable', 'ele-conditions' ),
			'type'      => \Elementor\Controls_Manager::SELECT,
			'options'   => [
				''              => __( '— select —', 'ele-conditions' ),
				// Post
				'ID'            => 'ID',
				'post_status'   => 'post_status',
				'post_type'     => 'post_type',
				'post_author'   => 'post_author',
				'post_author_id'=> 'post_author_id',
				'comment_count' => 'comment_count',
				// User
				'is_logged_in'  => 'is_logged_in',
				'user_id'       => 'user_id',
				'user_role'     => 'user_role',
				// Time
				'current_hour'  => 'current_hour  (0–23)',
				'current_day'   => 'current_day   (1=Mon … 7=Sun)',
				'current_month' => 'current_month (1–12)',
				'current_year'  => 'current_year',
				'current_date'  => 'current_date  (Y-m-d)',
				'now'           => 'now            (Y-m-d H:i:s)',
				// WooCommerce
				'cart_count'    => 'cart_count (WooCommerce)',
				'cart_total'    => 'cart_total (WooCommerce)',
				// Other
				'name'          => 'name',
				'post_excerpt'  => 'post_excerpt',
				'description'   => 'description',
				'permalink'     => 'permalink',
				'content'         => 'content',
				'content_length'  => 'content_length  (characters)',
				'excerpt_length'  => 'excerpt_length  (characters)',
				'acf_meta'        => __( 'ACF / Meta field (dropdown)…', 'ele-conditions' ),
				'custom'          => __( 'Type manually…', 'ele-conditions' ),
			],
			'default'   => '',
			'condition' => [ 'cond_type' => 'simple' ],
		]
	);

	// ACF / meta dropdown
	$repeater->add_control(
		'cond_var_acf_meta',
		[
			'label'       => __( 'Field', 'ele-conditions' ),
			'type'        => \Elementor\Controls_Manager::SELECT2,
			'options'     => elecond_get_meta_acf_options(),
			'label_block' => true,
			'condition'   => [
				'cond_type'       => 'simple',
				'cond_var_preset' => 'acf_meta',
			],
		]
	);

	$repeater->add_control(
		'cond_var_acf_manual',
		[
			'label'       => __( 'Field name', 'ele-conditions' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'placeholder' => __( 'e.g. my_field', 'ele-conditions' ),
			'label_block' => true,
			'condition'   => [
				'cond_type'        => 'simple',
				'cond_var_preset'  => 'acf_meta',
				'cond_var_acf_meta'=> '__manual__',
			],
		]
	);

	// Manual text input
	$repeater->add_control(
		'cond_var_custom',
		[
			'label'       => __( 'Variable name', 'ele-conditions' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'placeholder' => __( 'e.g. my_field', 'ele-conditions' ),
			'label_block' => true,
			'condition'   => [
				'cond_type'       => 'simple',
				'cond_var_preset' => 'custom',
			],
		]
	);

	$repeater->add_control(
		'cond_operator',
		[
			'label'     => __( 'Operator', 'ele-conditions' ),
			'type'      => \Elementor\Controls_Manager::SELECT,
			'options'   => [
				'=='  => '== (equal)',
				'!='  => '!= (not equal)',
				'===' => '=== (strict equal)',
				'!==' => '!== (strict not equal)',
				'>'   => '>  (greater than)',
				'<'   => '<  (less than)',
				'>='  => '>= (greater or equal)',
				'<='  => '<= (less or equal)',
			],
			'default'   => '==',
			'condition' => [ 'cond_type' => 'simple' ],
		]
	);

	$repeater->add_control(
		'cond_value',
		[
			'label'       => __( 'Value', 'ele-conditions' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'placeholder' => __( 'e.g. publish, administrator, true, 5', 'ele-conditions' ),
			'label_block' => true,
			'condition'   => [ 'cond_type' => 'simple' ],
		]
	);

	// ── Time interval ───────────────────────────────────────
	$repeater->add_control(
		'cond_time_from',
		[
			'label'       => __( 'From (HH:MM)', 'ele-conditions' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'placeholder' => '09:00',
			'label_block' => true,
			'condition'   => [ 'cond_type' => 'time_interval' ],
		]
	);

	$repeater->add_control(
		'cond_time_to',
		[
			'label'       => __( 'To (HH:MM)', 'ele-conditions' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'placeholder' => '17:00',
			'description' => __( 'Uses WordPress timezone. Cross-midnight supported (e.g. 22:00–06:00).', 'ele-conditions' ),
			'label_block' => true,
			'condition'   => [ 'cond_type' => 'time_interval' ],
		]
	);

	// ── Date/time interval ─────────────────────────────────
	$repeater->add_control(
		'cond_datetime_from',
		[
			'label'       => __( 'From', 'ele-conditions' ),
			'type'        => Elecond_Datetime_Control::TYPE,
			'label_block' => true,
			'condition'   => [ 'cond_type' => 'date_interval' ],
		]
	);

	$repeater->add_control(
		'cond_datetime_to',
		[
			'label'       => __( 'To', 'ele-conditions' ),
			'type'        => Elecond_Datetime_Control::TYPE,
			'description' => __( 'Uses WordPress timezone. Inclusive on both ends.', 'ele-conditions' ),
			'label_block' => true,
			'condition'   => [ 'cond_type' => 'date_interval' ],
		]
	);

	// ── AND / OR connector ──────────────────────────────────
	$repeater->add_control(
		'cond_logic',
		[
			'label'   => __( 'Connect next with', 'ele-conditions' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'options' => [
				'AND' => __( 'AND — all must match', 'ele-conditions' ),
				'OR'  => __( 'OR  — any must match', 'ele-conditions' ),
			],
			'default' => 'AND',
		]
	);

	$element->add_control(
		'conditions_list',
		[
			'label'       => __( 'Conditions', 'ele-conditions' ),
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'title_field' => '{{ cond_type === "time_interval" ? "⏱ " + cond_time_from + " – " + cond_time_to : cond_type === "date_interval" ? "📅 " + cond_datetime_from + " – " + cond_datetime_to : (cond_var_preset === "custom" ? cond_var_custom : cond_var_preset) + " " + cond_operator + " " + cond_value }}',
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

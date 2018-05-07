<?php

require_once ELECONDITIONS_DIR.'inc/parse_conditions.php';

add_action( 'elementor/element/before_section_start', function( $element, $section_id, $args ) {
   /** @var \Elementor\Element_Base $element */
   if ( '_section_responsive' === $section_id ) {

   	$element->start_controls_section(
   		'conditional_section',
   		[
   			'tab' => \Elementor\Controls_Manager::TAB_STYLE,
   			'label' => __( 'Conditions', 'elementor' ),
   		]
   	);

   	$element->add_control(
   		'element_condition',
   		[
   		'type' => \Elementor\Controls_Manager::TEXTAREA,
   		'label' => __( 'Write your conditions:', 'elementor' ),
   		]
   	);
     
    $element->add_control(
		'element_condition_info',
		[
			'label' => __( 'Currently the conditions work with 2 operands and 1 operator (==,!=,===,!==,<,>,<=,>=)' ),
			'type' => \Elementor\Controls_Manager::RAW_HTML,
		]
	);

   	$element->end_controls_section();
   }
}, 10, 3 );

/* if the condition is false we delete the content. Later maybe this should be stoped sooner, but for now I'll use it like this.*/
add_action( 'elementor/widget/render_content', function( $content, $widget ) {
  $settings = $widget->get_active_settings();
  if ( !isset($settings['element_condition']) || $settings['element_condition']=='' ) return $content;
  if ( parse_condition($settings['element_condition']) ) return $content;
  return '';
}, 10, 2 );
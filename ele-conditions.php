<?php
/*
 * Plugin Name: Ele Conditions for Elementor
 * Version: 1.0.7
 * Description: Conditional display logic for Elementor elements and widgets.
 * Plugin URI: https://www.eletemplator.com
 * Author: Liviu Duda
 * Author URI: https://www.leadpro.ro
 * Text Domain: ele-conditions
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
define( 'ELECONDITIONS_DIR', plugin_dir_path( __FILE__ ));
require_once ELECONDITIONS_DIR.'inc/controls.php';

// Add custom keywords to the eletheme
add_filter( 'eleconditions_vars', 'elecond_keywords');
function elecond_keywords( $custom_vars ) {
    $custom_vars['now']=gmdate('Y-m-d H:i:s');
    return $custom_vars;
}
<?php
/*
 * Plugin Name: Ele Conditions for Elementor
 * Version: 1.0.11
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
define( 'ELECONDITIONS_DIR', plugin_dir_path( __FILE__ ) );
require_once ELECONDITIONS_DIR . 'inc/controls.php';

add_filter( 'eleconditions_vars', 'elecond_keywords' );
function elecond_keywords( $custom_vars ) {
	global $post;

	// Date/time in WordPress timezone
	$tz     = wp_timezone();
	$now_dt = new DateTime( 'now', $tz );

	$custom_vars['now']           = $now_dt->format( 'Y-m-d H:i:s' );
	$custom_vars['current_date']  = $now_dt->format( 'Y-m-d' );
	$custom_vars['current_hour']  = (int) $now_dt->format( 'G' );  // 0–23
	$custom_vars['current_day']   = (int) $now_dt->format( 'N' );  // 1=Mon, 7=Sun
	$custom_vars['current_month'] = (int) $now_dt->format( 'n' );  // 1–12
	$custom_vars['current_year']  = (int) $now_dt->format( 'Y' );

	// Post
	if ( isset( $post->ID ) ) {
		$custom_vars['comment_count']  = (int) get_comments_number( $post->ID );
		$custom_vars['post_author']    = get_the_author_meta( 'login', $post->post_author );
		$custom_vars['post_author_id'] = (int) $post->post_author;
		$custom_vars['post_status']    = $post->post_status;
		$custom_vars['post_type']      = $post->post_type;
		$custom_vars['content_length'] = mb_strlen( wp_strip_all_tags( $post->post_content ) );
		$custom_vars['excerpt_length'] = mb_strlen( wp_strip_all_tags( get_the_excerpt( $post->ID ) ) );
	}

	// Current user
	$user = wp_get_current_user();
	$custom_vars['user_id']      = $user->ID;
	$custom_vars['user_role']    = ! empty( $user->roles ) ? $user->roles[0] : '';
	$custom_vars['is_logged_in'] = is_user_logged_in() ? 'true' : 'false';

	// WooCommerce
	if ( function_exists( 'WC' ) && WC()->cart ) {
		$custom_vars['cart_count'] = WC()->cart->get_cart_contents_count();
		$custom_vars['cart_total'] = (float) WC()->cart->subtotal;
	}

	return $custom_vars;
}

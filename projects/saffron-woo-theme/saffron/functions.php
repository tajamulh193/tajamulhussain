<?php
/**
 * Saffron theme functions.
 *
 * @package Saffron
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SAFFRON_VERSION', '1.0.0' );

/**
 * Theme supports, including WooCommerce product gallery features.
 */
function saffron_setup() {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
	add_editor_style( 'style.css' );
}
add_action( 'after_setup_theme', 'saffron_setup' );

/**
 * Front-end styles and the two Google Fonts used by theme.json.
 */
function saffron_assets() {
	wp_enqueue_style(
		'saffron-fonts',
		'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Jost:wght@400;500;600&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'saffron-style', get_stylesheet_uri(), array( 'saffron-fonts' ), SAFFRON_VERSION );
}
add_action( 'wp_enqueue_scripts', 'saffron_assets' );

/**
 * Pattern category so the theme's patterns are grouped in the inserter.
 */
function saffron_pattern_category() {
	register_block_pattern_category( 'saffron', array( 'label' => __( 'Saffron', 'saffron' ) ) );
}
add_action( 'init', 'saffron_pattern_category' );

/**
 * Extra block styles: a soft card style for groups and an outline button.
 */
function saffron_block_styles() {
	register_block_style(
		'core/group',
		array(
			'name'         => 'saffron-card',
			'label'        => __( 'Card', 'saffron' ),
			'inline_style' => '.wp-block-group.is-style-saffron-card{background:var(--wp--preset--color--cream);border-radius:18px;padding:2rem}',
		)
	);
	register_block_style(
		'core/button',
		array(
			'name'         => 'saffron-outline',
			'label'        => __( 'Outline', 'saffron' ),
			'inline_style' => '.wp-block-button.is-style-saffron-outline .wp-block-button__link{background:transparent;color:var(--wp--preset--color--ink);border:1px solid currentColor}',
		)
	);
}
add_action( 'init', 'saffron_block_styles' );

/**
 * Show four products per row and twelve per page in WooCommerce archives.
 *
 * @return int
 */
function saffron_loop_columns() {
	return 4;
}
add_filter( 'loop_shop_columns', 'saffron_loop_columns' );

/**
 * Products per page.
 *
 * @return int
 */
function saffron_products_per_page() {
	return 12;
}
add_filter( 'loop_shop_per_page', 'saffron_products_per_page' );

/**
 * Free-shipping progress message in the mini cart, when WooCommerce is active.
 */
function saffron_free_shipping_notice() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}
	$threshold = (float) apply_filters( 'saffron_free_shipping_threshold', 75 );
	$subtotal  = (float) WC()->cart->get_subtotal();
	if ( $subtotal <= 0 ) {
		return;
	}
	if ( $subtotal >= $threshold ) {
		echo '<p class="saffron-ship">' . esc_html__( 'Your order ships free.', 'saffron' ) . '</p>';
		return;
	}
	printf(
		'<p class="saffron-ship">%s</p>',
		sprintf(
			/* translators: %s: amount left to spend */
			esc_html__( 'Spend %s more for free shipping.', 'saffron' ),
			wp_kses_post( wc_price( $threshold - $subtotal ) )
		)
	);
}
add_action( 'woocommerce_before_mini_cart', 'saffron_free_shipping_notice' );

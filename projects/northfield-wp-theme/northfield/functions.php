<?php
/**
 * Northfield theme functions.
 *
 * @package Northfield
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NORTHFIELD_VERSION', '1.0.0' );

/**
 * Theme setup: supports, menus and image sizes.
 */
function northfield_setup() {
	load_theme_textdomain( 'northfield', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 80,
			'width'       => 280,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	register_nav_menus(
		array(
			'primary' => __( 'Primary menu', 'northfield' ),
			'footer'  => __( 'Footer menu', 'northfield' ),
		)
	);

	add_image_size( 'northfield-card', 800, 500, true );
}
add_action( 'after_setup_theme', 'northfield_setup' );

/**
 * Enqueue styles and scripts. File modification time is used as the version
 * so browsers fetch fresh files after every change.
 */
function northfield_assets() {
	wp_enqueue_style(
		'northfield-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Newsreader:ital,opsz,wght@0,6..72,500;0,6..72,600;1,6..72,500&display=swap',
		array(),
		null
	);
	wp_enqueue_style(
		'northfield-style',
		get_stylesheet_uri(),
		array( 'northfield-fonts' ),
		filemtime( get_stylesheet_directory() . '/style.css' )
	);
	wp_enqueue_script(
		'northfield-nav',
		get_template_directory_uri() . '/assets/js/nav.js',
		array(),
		NORTHFIELD_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'northfield_assets' );

/**
 * Case Studies post type and Sector taxonomy.
 */
function northfield_register_content_types() {
	register_post_type(
		'case_study',
		array(
			'labels'       => array(
				'name'          => __( 'Case Studies', 'northfield' ),
				'singular_name' => __( 'Case Study', 'northfield' ),
				'add_new_item'  => __( 'Add New Case Study', 'northfield' ),
				'edit_item'     => __( 'Edit Case Study', 'northfield' ),
				'all_items'     => __( 'All Case Studies', 'northfield' ),
			),
			'public'       => true,
			'has_archive'  => 'case-studies',
			'rewrite'      => array( 'slug' => 'case-studies' ),
			'menu_icon'    => 'dashicons-portfolio',
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
			'show_in_rest' => true,
		)
	);

	register_taxonomy(
		'sector',
		'case_study',
		array(
			'labels'       => array(
				'name'          => __( 'Sectors', 'northfield' ),
				'singular_name' => __( 'Sector', 'northfield' ),
			),
			'hierarchical' => true,
			'public'       => true,
			'rewrite'      => array( 'slug' => 'sector' ),
			'show_in_rest' => true,
		)
	);
}
add_action( 'init', 'northfield_register_content_types' );

/**
 * Footer widget area.
 */
function northfield_widgets() {
	register_sidebar(
		array(
			'name'          => __( 'Footer about', 'northfield' ),
			'id'            => 'footer-about',
			'description'   => __( 'Short text shown in the first footer column.', 'northfield' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'northfield_widgets' );

/**
 * Customizer: homepage hero and contact details.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function northfield_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'northfield_home',
		array(
			'title'    => __( 'Homepage', 'northfield' ),
			'priority' => 30,
		)
	);

	$fields = array(
		'hero_title'   => array(
			'label'   => __( 'Hero heading', 'northfield' ),
			'default' => __( 'Clear advice for growing businesses.', 'northfield' ),
			'type'    => 'text',
			'sanitize' => 'sanitize_text_field',
		),
		'hero_text'    => array(
			'label'   => __( 'Hero text', 'northfield' ),
			'default' => __( 'We help owner-led companies plan, fix operations and make confident decisions, with practical work that your team can keep running.', 'northfield' ),
			'type'    => 'textarea',
			'sanitize' => 'sanitize_textarea_field',
		),
		'hero_button'  => array(
			'label'   => __( 'Hero button text', 'northfield' ),
			'default' => __( 'Talk to us', 'northfield' ),
			'type'    => 'text',
			'sanitize' => 'sanitize_text_field',
		),
		'hero_url'     => array(
			'label'   => __( 'Hero button link', 'northfield' ),
			'default' => '',
			'type'    => 'url',
			'sanitize' => 'esc_url_raw',
		),
		'contact_mail' => array(
			'label'   => __( 'Contact form recipient email', 'northfield' ),
			'default' => '',
			'type'    => 'text',
			'sanitize' => 'sanitize_email',
		),
	);

	foreach ( $fields as $id => $field ) {
		$wp_customize->add_setting(
			'northfield_' . $id,
			array(
				'default'           => $field['default'],
				'sanitize_callback' => $field['sanitize'],
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'northfield_' . $id,
			array(
				'label'   => $field['label'],
				'section' => 'northfield_home',
				'type'    => $field['type'],
			)
		);
	}
}
add_action( 'customize_register', 'northfield_customize_register' );

/**
 * Read a theme option with its default.
 *
 * @param string $key Option key without the prefix.
 * @return string
 */
function northfield_opt( $key ) {
	$defaults = array(
		'hero_title'   => __( 'Clear advice for growing businesses.', 'northfield' ),
		'hero_text'    => __( 'We help owner-led companies plan, fix operations and make confident decisions, with practical work that your team can keep running.', 'northfield' ),
		'hero_button'  => __( 'Talk to us', 'northfield' ),
		'hero_url'     => '',
		'contact_mail' => '',
	);
	$default = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
	return (string) get_theme_mod( 'northfield_' . $key, $default );
}

/**
 * Link target for the main call to action: the customizer URL, the Contact
 * page, or the homepage as a last resort.
 *
 * @return string
 */
function northfield_cta_url() {
	$url = northfield_opt( 'hero_url' );
	if ( $url ) {
		return $url;
	}
	$contact = get_page_by_path( 'contact' );
	return $contact ? get_permalink( $contact ) : home_url( '/' );
}

/**
 * Shorter excerpts.
 */
add_filter(
	'excerpt_length',
	function () {
		return 24;
	}
);
add_filter(
	'excerpt_more',
	function () {
		return '&hellip;';
	}
);

/**
 * Shortcode: [northfield_button url="/contact/" text="Talk to us"].
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function northfield_button_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'url'  => northfield_cta_url(),
			'text' => __( 'Talk to us', 'northfield' ),
		),
		$atts,
		'northfield_button'
	);
	return sprintf(
		'<a class="nf-btn" href="%s">%s</a>',
		esc_url( $atts['url'] ),
		esc_html( $atts['text'] )
	);
}
add_shortcode( 'northfield_button', 'northfield_button_shortcode' );

/**
 * Contact form handler. Runs for logged-in and logged-out visitors.
 * Checks a nonce and a honeypot field, validates input, sends the email with
 * wp_mail() and redirects back with a status flag.
 */
function northfield_handle_contact() {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$back = remove_query_arg( array( 'nf_status' ), $back );

	if ( ! isset( $_POST['northfield_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['northfield_nonce'] ) ), 'northfield_contact' ) ) {
		wp_safe_redirect( add_query_arg( 'nf_status', 'error', $back ) );
		exit;
	}

	// Honeypot: real visitors never fill this in.
	if ( ! empty( $_POST['nf_website'] ) ) {
		wp_safe_redirect( add_query_arg( 'nf_status', 'sent', $back ) );
		exit;
	}

	$name    = isset( $_POST['nf_name'] ) ? sanitize_text_field( wp_unslash( $_POST['nf_name'] ) ) : '';
	$email   = isset( $_POST['nf_email'] ) ? sanitize_email( wp_unslash( $_POST['nf_email'] ) ) : '';
	$message = isset( $_POST['nf_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['nf_message'] ) ) : '';

	if ( '' === $name || ! is_email( $email ) || strlen( $message ) < 10 ) {
		wp_safe_redirect( add_query_arg( 'nf_status', 'invalid', $back ) );
		exit;
	}

	$to = northfield_opt( 'contact_mail' );
	if ( ! is_email( $to ) ) {
		$to = get_option( 'admin_email' );
	}

	$subject = sprintf( '[%s] New enquiry from %s', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $name );
	$body    = "Name: {$name}\nEmail: {$email}\n\n{$message}\n";
	$headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' );

	$sent = wp_mail( $to, $subject, $body, $headers );
	wp_safe_redirect( add_query_arg( 'nf_status', $sent ? 'sent' : 'error', $back ) );
	exit;
}
add_action( 'admin_post_nopriv_northfield_contact', 'northfield_handle_contact' );
add_action( 'admin_post_northfield_contact', 'northfield_handle_contact' );

/**
 * Print the first sector name of a case study, if it has one.
 *
 * @param int $post_id Post ID.
 * @return string Escaped sector name or empty string.
 */
function northfield_sector_name( $post_id ) {
	$terms = get_the_terms( $post_id, 'sector' );
	if ( $terms && ! is_wp_error( $terms ) ) {
		return esc_html( $terms[0]->name );
	}
	return '';
}

/**
 * Fallback menu when none is assigned.
 */
function northfield_fallback_menu() {
	echo '<ul>';
	echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'northfield' ) . '</a></li>';
	echo '</ul>';
}

<?php
/**
 * Site header.
 *
 * @package Northfield
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#content"><?php esc_html_e( 'Skip to content', 'northfield' ); ?></a>

<header class="nf-header">
	<div class="nf-wrap nf-header-row">
		<?php if ( has_custom_logo() ) : ?>
			<?php the_custom_logo(); ?>
		<?php else : ?>
			<a class="nf-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
		<?php endif; ?>

		<button class="nf-toggle" aria-expanded="false" aria-controls="nf-nav"><?php esc_html_e( 'Menu', 'northfield' ); ?></button>
		<nav id="nf-nav" class="nf-nav" aria-label="<?php esc_attr_e( 'Primary', 'northfield' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'fallback_cb'    => 'northfield_fallback_menu',
					'depth'          => 1,
				)
			);
			?>
		</nav>
	</div>
</header>

<main id="content">

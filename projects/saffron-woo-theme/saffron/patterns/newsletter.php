<?php
/**
 * Title: Newsletter sign-up
 * Slug: saffron/newsletter
 * Categories: saffron, call-to-action
 * Description: A centred heading and short text inviting visitors to join the mailing list.
 *
 * @package Saffron
 */
?>
<!-- wp:group {"align":"wide","layout":{"type":"constrained","contentSize":"560px"}} -->
<div class="wp-block-group alignwide"><!-- wp:heading {"textAlign":"center","fontSize":"x-large"} -->
<h2 class="wp-block-heading has-text-align-center has-x-large-font-size"><?php esc_html_e( 'Ten per cent off your first order', 'saffron' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","textColor":"stone"} -->
<p class="has-text-align-center has-stone-color has-text-color"><?php esc_html_e( 'One email a month: new prints, studio news and early access to sales.', 'saffron' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/newsletter/' ) ); ?>"><?php esc_html_e( 'Join the list', 'saffron' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

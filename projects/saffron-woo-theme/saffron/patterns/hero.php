<?php
/**
 * Title: Shop hero
 * Slug: saffron/hero
 * Categories: saffron, banner
 * Description: A large two-column banner with a headline, short text and two buttons.
 *
 * @package Saffron
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"5rem","bottom":"5rem"}}},"backgroundColor":"cream","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-cream-background-color has-background" style="padding-top:5rem;padding-bottom:5rem"><!-- wp:columns {"verticalAlignment":"center","align":"wide"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:paragraph {"style":{"typography":{"letterSpacing":"0.18em","textTransform":"uppercase"}},"textColor":"madder","fontSize":"small"} -->
<p class="has-madder-color has-text-color has-small-font-size" style="letter-spacing:0.18em;text-transform:uppercase"><?php esc_html_e( 'Autumn collection', 'saffron' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"fontSize":"huge"} -->
<h1 class="wp-block-heading has-huge-font-size"><?php esc_html_e( 'Printed by hand, made to be worn for years', 'saffron' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Cotton and linen pieces block-printed with natural dyes in small batches.', 'saffron' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/shop/' ) ); ?>"><?php esc_html_e( 'Shop the collection', 'saffron' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-saffron-outline"} -->
<div class="wp-block-button is-style-saffron-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'Our process', 'saffron' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:image {"aspectRatio":"4/5","scale":"cover","style":{"border":{"radius":"200px 200px 18px 18px"}}} -->
<figure class="wp-block-image has-custom-border"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/hero.svg' ) ); ?>" alt="<?php esc_attr_e( 'Block-printed scarves on a wooden table', 'saffron' ); ?>" style="border-radius:200px 200px 18px 18px;aspect-ratio:4/5;object-fit:cover"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

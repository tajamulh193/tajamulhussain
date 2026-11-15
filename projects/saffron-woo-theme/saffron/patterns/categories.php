<?php
/**
 * Title: Category tiles
 * Slug: saffron/categories
 * Categories: saffron, featured
 * Description: Three category tiles with titles and links.
 *
 * @package Saffron
 */

$saffron_tiles = array(
	array( __( 'Scarves', 'saffron' ), '/product-category/scarves/' ),
	array( __( 'Home linen', 'saffron' ), '/product-category/home/' ),
	array( __( 'Gifts', 'saffron' ), '/product-category/gifts/' ),
);
?>
<!-- wp:columns {"align":"wide","style":{"spacing":{"margin":{"top":"4rem"}}}} -->
<div class="wp-block-columns alignwide" style="margin-top:4rem">
<?php foreach ( $saffron_tiles as $saffron_tile ) : ?>
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"className":"is-style-saffron-card saffron-card"} -->
<div class="wp-block-group is-style-saffron-card saffron-card"><!-- wp:heading {"level":3,"fontSize":"x-large"} -->
<h3 class="wp-block-heading has-x-large-font-size"><?php echo esc_html( $saffron_tile[0] ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><a href="<?php echo esc_url( home_url( $saffron_tile[1] ) ); ?>"><?php esc_html_e( 'Browse', 'saffron' ); ?> →</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->
<?php endforeach; ?>
</div>
<!-- /wp:columns -->

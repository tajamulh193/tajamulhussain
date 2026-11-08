<?php
/**
 * 404 template.
 *
 * @package Northfield
 */

get_header();
?>
<div class="nf-page-head">
	<div class="nf-wrap nf-narrow"><h1><?php esc_html_e( 'That page could not be found', 'northfield' ); ?></h1></div>
</div>
<div class="nf-content">
	<div class="nf-wrap nf-narrow">
		<p><?php esc_html_e( 'The link may be old or mistyped. Search the site, or go back to the homepage.', 'northfield' ); ?></p>
		<?php get_search_form(); ?>
		<p><a class="nf-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to the homepage', 'northfield' ); ?></a></p>
	</div>
</div>
<?php
get_footer();

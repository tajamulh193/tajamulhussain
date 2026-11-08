<?php
/**
 * Fallback template: blog index, search results and generic archives.
 *
 * @package Northfield
 */

get_header();
?>

<div class="nf-page-head">
	<div class="nf-wrap">
		<?php if ( is_search() ) : ?>
			<h1>
				<?php
				/* translators: %s: search query. */
				printf( esc_html__( 'Search results for "%s"', 'northfield' ), esc_html( get_search_query() ) );
				?>
			</h1>
		<?php elseif ( is_archive() ) : ?>
			<h1><?php the_archive_title(); ?></h1>
			<?php the_archive_description( '<p>', '</p>' ); ?>
		<?php else : ?>
			<h1><?php esc_html_e( 'Insights', 'northfield' ); ?></h1>
			<p><?php esc_html_e( 'Short, practical notes for owners and managers.', 'northfield' ); ?></p>
		<?php endif; ?>
	</div>
</div>

<div class="nf-content">
	<div class="nf-wrap">
		<?php if ( have_posts() ) : ?>
			<ul class="nf-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/card' );
				endwhile;
				?>
			</ul>
			<nav class="nf-pagination" aria-label="<?php esc_attr_e( 'Pagination', 'northfield' ); ?>">
				<?php
				echo wp_kses_post(
					paginate_links(
						array(
							'prev_text' => __( 'Previous', 'northfield' ),
							'next_text' => __( 'Next', 'northfield' ),
						)
					)
				);
				?>
			</nav>
		<?php else : ?>
			<p><?php esc_html_e( 'Nothing found here yet. Try another search or return to the homepage.', 'northfield' ); ?></p>
			<?php get_search_form(); ?>
		<?php endif; ?>
	</div>
</div>

<?php
get_footer();

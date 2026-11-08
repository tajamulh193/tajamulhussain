<?php
/**
 * Case studies archive, with sector filter links. Also used for sector pages.
 *
 * @package Northfield
 */

get_header();

$sectors = get_terms(
	array(
		'taxonomy'   => 'sector',
		'hide_empty' => true,
	)
);
$current = is_tax( 'sector' ) ? get_queried_object_id() : 0;
?>

<div class="nf-page-head">
	<div class="nf-wrap">
		<h1><?php echo is_tax( 'sector' ) ? esc_html( single_term_title( '', false ) ) : esc_html__( 'Case studies', 'northfield' ); ?></h1>
		<p><?php esc_html_e( 'Sample engagements written up as short stories: the problem, the work, the result.', 'northfield' ); ?></p>
	</div>
</div>

<div class="nf-content">
	<div class="nf-wrap">
		<?php if ( $sectors && ! is_wp_error( $sectors ) ) : ?>
			<nav class="nf-filters" aria-label="<?php esc_attr_e( 'Filter by sector', 'northfield' ); ?>">
				<a href="<?php echo esc_url( get_post_type_archive_link( 'case_study' ) ); ?>" class="<?php echo $current ? '' : 'is-current'; ?>"<?php echo $current ? '' : ' aria-current="page"'; ?>><?php esc_html_e( 'All', 'northfield' ); ?></a>
				<?php foreach ( $sectors as $term ) : ?>
					<?php $is_current = ( (int) $term->term_id === (int) $current ); ?>
					<a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="<?php echo $is_current ? 'is-current' : ''; ?>"<?php echo $is_current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $term->name ); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>

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
				<?php echo wp_kses_post( paginate_links() ); ?>
			</nav>
		<?php else : ?>
			<p><?php esc_html_e( 'No case studies in this sector yet.', 'northfield' ); ?></p>
		<?php endif; ?>
	</div>
</div>

<?php
get_footer();

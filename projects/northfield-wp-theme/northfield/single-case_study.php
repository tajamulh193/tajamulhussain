<?php
/**
 * Single case study.
 *
 * @package Northfield
 */

get_header();

while ( have_posts() ) :
	the_post();
	$sector = northfield_sector_name( get_the_ID() );
	?>
	<article <?php post_class(); ?>>
		<header class="nf-page-head">
			<div class="nf-wrap nf-narrow">
				<?php if ( $sector ) : ?>
					<span class="nf-kicker"><?php echo $sector; // Escaped in northfield_sector_name(). ?></span>
				<?php endif; ?>
				<h1><?php the_title(); ?></h1>
				<?php if ( has_excerpt() ) : ?>
					<p><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>
			</div>
		</header>
		<div class="nf-content">
			<div class="nf-wrap">
				<?php if ( has_post_thumbnail() ) : ?>
					<div style="max-width:46rem;margin:0 auto 2rem"><?php the_post_thumbnail( 'large' ); ?></div>
				<?php endif; ?>
				<div class="entry-content"><?php the_content(); ?></div>
				<nav class="nf-post-nav" style="max-width:46rem;margin-left:auto;margin-right:auto" aria-label="<?php esc_attr_e( 'Case studies', 'northfield' ); ?>">
					<?php previous_post_link( '%link', '&larr; %title' ); ?>
					<?php next_post_link( '%link', '%title &rarr;' ); ?>
				</nav>
			</div>
		</div>
	</article>
	<?php
endwhile;

get_footer();

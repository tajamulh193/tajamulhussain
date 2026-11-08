<?php
/**
 * Single blog post.
 *
 * @package Northfield
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class(); ?>>
		<header class="nf-page-head">
			<div class="nf-wrap nf-narrow">
				<span class="nf-kicker"><?php echo esc_html( get_the_date() ); ?></span>
				<h1><?php the_title(); ?></h1>
				<div class="entry-meta">
					<?php
					/* translators: %s: author name. */
					printf( esc_html__( 'By %s', 'northfield' ), esc_html( get_the_author() ) );
					?>
				</div>
			</div>
		</header>
		<div class="nf-content">
			<div class="nf-wrap">
				<div class="entry-content"><?php the_content(); ?></div>
				<div class="nf-tags" style="max-width:46rem;margin:0 auto"><?php the_tags( '', '', '' ); ?></div>
				<nav class="nf-post-nav" style="max-width:46rem;margin-left:auto;margin-right:auto" aria-label="<?php esc_attr_e( 'Posts', 'northfield' ); ?>">
					<?php previous_post_link( '%link', '&larr; %title' ); ?>
					<?php next_post_link( '%link', '%title &rarr;' ); ?>
				</nav>
			</div>
		</div>
	</article>
	<?php
endwhile;

get_footer();

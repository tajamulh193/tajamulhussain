<?php
/**
 * Standard page.
 *
 * @package Northfield
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class(); ?>>
		<header class="nf-page-head">
			<div class="nf-wrap nf-narrow"><h1><?php the_title(); ?></h1></div>
		</header>
		<div class="nf-content">
			<div class="nf-wrap nf-narrow">
				<div class="entry-content"><?php the_content(); ?></div>
			</div>
		</div>
	</article>
	<?php
endwhile;

get_footer();

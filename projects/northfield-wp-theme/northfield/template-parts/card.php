<?php
/**
 * Card used for posts and case studies in grids.
 *
 * @package Northfield
 */

$is_case = ( 'case_study' === get_post_type() );
$kicker  = $is_case ? northfield_sector_name( get_the_ID() ) : get_the_date();
?>
<li>
	<article <?php post_class( 'nf-card' ); ?>>
		<a class="nf-card-media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'northfield-card', array( 'loading' => 'lazy' ) ); ?>
			<?php else : ?>
				<span class="nf-card-fallback"><?php echo esc_html( mb_substr( get_the_title(), 0, 1 ) ); ?></span>
			<?php endif; ?>
		</a>
		<div class="nf-card-body">
			<?php if ( $kicker ) : ?>
				<span class="nf-kicker"><?php echo $kicker; // Already escaped above. ?></span>
			<?php endif; ?>
			<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
			<p><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ) ); ?></p>
		</div>
	</article>
</li>

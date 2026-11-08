<?php
/**
 * Homepage template.
 *
 * @package Northfield
 */

get_header();

$services = array(
	array(
		__( 'Strategy and planning', 'northfield' ),
		__( 'Annual plans, budgets and a short list of priorities your whole team understands.', 'northfield' ),
	),
	array(
		__( 'Operations improvement', 'northfield' ),
		__( 'We map how work really flows, remove the bottlenecks and write down the new routine.', 'northfield' ),
	),
	array(
		__( 'Project delivery', 'northfield' ),
		__( 'Interim project management for launches, systems changes and office moves.', 'northfield' ),
	),
);
?>

<section class="nf-hero">
	<div class="nf-wrap">
		<h1><?php echo esc_html( northfield_opt( 'hero_title' ) ); ?></h1>
		<p><?php echo esc_html( northfield_opt( 'hero_text' ) ); ?></p>
		<div class="nf-actions">
			<a class="nf-btn" href="<?php echo esc_url( northfield_cta_url() ); ?>"><?php echo esc_html( northfield_opt( 'hero_button' ) ); ?></a>
			<a class="nf-btn nf-btn--ghost" href="<?php echo esc_url( get_post_type_archive_link( 'case_study' ) ); ?>"><?php esc_html_e( 'See our work', 'northfield' ); ?></a>
		</div>
	</div>
</section>

<section class="nf-section">
	<div class="nf-wrap">
		<h2><?php esc_html_e( 'What we do', 'northfield' ); ?></h2>
		<p class="nf-lede"><?php esc_html_e( 'Three kinds of work, each with a clear start, a clear finish and a written handover.', 'northfield' ); ?></p>
		<ul class="nf-services">
			<?php foreach ( $services as $s ) : ?>
				<li>
					<h3><?php echo esc_html( $s[0] ); ?></h3>
					<p><?php echo esc_html( $s[1] ); ?></p>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

<?php
$cases = new WP_Query(
	array(
		'post_type'           => 'case_study',
		'posts_per_page'      => 3,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);
if ( $cases->have_posts() ) :
	?>
<section class="nf-section nf-section--tint">
	<div class="nf-wrap">
		<div class="nf-head-row">
			<div>
				<h2><?php esc_html_e( 'Recent case studies', 'northfield' ); ?></h2>
				<p class="nf-lede"><?php esc_html_e( 'Sample engagements written up as short stories: the problem, the work, the result.', 'northfield' ); ?></p>
			</div>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'case_study' ) ); ?>"><?php esc_html_e( 'All case studies', 'northfield' ); ?></a>
		</div>
		<ul class="nf-grid">
			<?php
			while ( $cases->have_posts() ) :
				$cases->the_post();
				get_template_part( 'template-parts/card' );
			endwhile;
			wp_reset_postdata();
			?>
		</ul>
	</div>
</section>
<?php endif; ?>

<?php
$posts = new WP_Query(
	array(
		'post_type'           => 'post',
		'posts_per_page'      => 3,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);
if ( $posts->have_posts() ) :
	?>
<section class="nf-section">
	<div class="nf-wrap">
		<h2><?php esc_html_e( 'Latest insights', 'northfield' ); ?></h2>
		<p class="nf-lede"><?php esc_html_e( 'Short, practical notes for owners and managers.', 'northfield' ); ?></p>
		<ul class="nf-grid">
			<?php
			while ( $posts->have_posts() ) :
				$posts->the_post();
				get_template_part( 'template-parts/card' );
			endwhile;
			wp_reset_postdata();
			?>
		</ul>
	</div>
</section>
<?php endif; ?>

<section class="nf-section nf-cta">
	<div class="nf-wrap">
		<h2><?php esc_html_e( 'Have a project in mind?', 'northfield' ); ?></h2>
		<p class="nf-lede"><?php esc_html_e( 'Tell us what you need and when. We reply within two working days.', 'northfield' ); ?></p>
		<a class="nf-btn" href="<?php echo esc_url( northfield_cta_url() ); ?>"><?php esc_html_e( 'Start a conversation', 'northfield' ); ?></a>
	</div>
</section>

<?php
get_footer();

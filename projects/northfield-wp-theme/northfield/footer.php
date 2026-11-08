<?php
/**
 * Site footer.
 *
 * @package Northfield
 */
?>
</main>

<footer class="nf-footer">
	<div class="nf-wrap">
		<div class="nf-footer-grid">
			<div>
				<?php if ( is_active_sidebar( 'footer-about' ) ) : ?>
					<?php dynamic_sidebar( 'footer-about' ); ?>
				<?php else : ?>
					<h2 class="widget-title"><?php bloginfo( 'name' ); ?></h2>
					<p><?php bloginfo( 'description' ); ?></p>
				<?php endif; ?>
			</div>
			<div>
				<h2><?php esc_html_e( 'Explore', 'northfield' ); ?></h2>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'fallback_cb'    => false,
						'depth'          => 1,
					)
				);
				?>
			</div>
			<div>
				<h2><?php esc_html_e( 'Contact', 'northfield' ); ?></h2>
				<p><a href="<?php echo esc_url( northfield_cta_url() ); ?>"><?php esc_html_e( 'Send us a message', 'northfield' ); ?></a></p>
			</div>
		</div>
		<div class="nf-copy">
			&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>.
			<?php esc_html_e( 'Northfield is a sample theme by Tujummal Hussain.', 'northfield' ); ?>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>

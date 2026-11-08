<?php
/**
 * Template Name: Contact
 *
 * Contact page with a form that posts to admin-post.php. See
 * northfield_handle_contact() in functions.php.
 *
 * @package Northfield
 */

get_header();

// Status flag set by the form handler. Only known values are accepted.
$status = isset( $_GET['nf_status'] ) ? sanitize_key( wp_unslash( $_GET['nf_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$notice = array(
	'sent'    => array( __( 'Thank you. Your message has been sent and we will reply within two working days.', 'northfield' ), false ),
	'invalid' => array( __( 'Please enter your name, a valid email address and a message of at least ten characters.', 'northfield' ), true ),
	'error'   => array( __( 'Your message could not be sent. Please try again or email us directly.', 'northfield' ), true ),
);

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

				<?php if ( isset( $notice[ $status ] ) ) : ?>
					<p class="nf-notice<?php echo $notice[ $status ][1] ? ' nf-notice--error' : ''; ?>" role="status"><?php echo esc_html( $notice[ $status ][0] ); ?></p>
				<?php endif; ?>

				<form class="nf-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="northfield_contact">
					<?php wp_nonce_field( 'northfield_contact', 'northfield_nonce' ); ?>
					<div class="nf-hp" aria-hidden="true">
						<label for="nf_website"><?php esc_html_e( 'Leave this field empty', 'northfield' ); ?></label>
						<input type="text" id="nf_website" name="nf_website" tabindex="-1" autocomplete="off">
					</div>
					<div>
						<label for="nf_name"><?php esc_html_e( 'Name', 'northfield' ); ?></label>
						<input type="text" id="nf_name" name="nf_name" autocomplete="name" required>
					</div>
					<div>
						<label for="nf_email"><?php esc_html_e( 'Email', 'northfield' ); ?></label>
						<input type="email" id="nf_email" name="nf_email" autocomplete="email" required>
					</div>
					<div>
						<label for="nf_message"><?php esc_html_e( 'How can we help?', 'northfield' ); ?></label>
						<textarea id="nf_message" name="nf_message" rows="6" minlength="10" required></textarea>
					</div>
					<div><button class="nf-btn" type="submit"><?php esc_html_e( 'Send message', 'northfield' ); ?></button></div>
				</form>
			</div>
		</div>
	</article>
	<?php
endwhile;

get_footer();

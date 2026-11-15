<?php
/**
 * [slotwise_booking] shortcode: the front-end booking form.
 *
 * @package Slotwise
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the form and loads its script only on pages that use it.
 */
class Slotwise_Shortcode {

	/**
	 * Register shortcode and assets.
	 */
	public static function register() {
		add_shortcode( 'slotwise_booking', array( __CLASS__, 'render' ) );
		wp_register_style( 'slotwise', SLOTWISE_URL . 'assets/slotwise.css', array(), SLOTWISE_VERSION );
		wp_register_script( 'slotwise', SLOTWISE_URL . 'assets/slotwise.js', array(), SLOTWISE_VERSION, true );
	}

	/**
	 * Output the form.
	 *
	 * @param array $atts Shortcode attributes: service (optional ID to preselect).
	 * @return string
	 */
	public static function render( $atts ) {
		$atts     = shortcode_atts( array( 'service' => 0 ), $atts, 'slotwise_booking' );
		$services = get_posts(
			array(
				'post_type'      => 'sw_service',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
			)
		);
		if ( ! $services ) {
			return '<p>' . esc_html__( 'Online booking is not open yet.', 'slotwise' ) . '</p>';
		}

		wp_enqueue_style( 'slotwise' );
		wp_enqueue_script( 'slotwise' );
		wp_localize_script(
			'slotwise',
			'SLOTWISE',
			array(
				'root'  => esc_url_raw( rest_url( 'slotwise/v1/' ) ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'i18n'  => array(
					'loading' => __( 'Checking availability…', 'slotwise' ),
					'none'    => __( 'No times left on this day. Please try another date.', 'slotwise' ),
					'error'   => __( 'Something went wrong. Please try again.', 'slotwise' ),
				),
			)
		);

		$s   = Slotwise_Settings::get();
		$min = wp_date( 'Y-m-d' );
		$max = wp_date( 'Y-m-d', strtotime( '+' . (int) $s['max_days'] . ' days' ) );

		ob_start();
		?>
		<form class="slotwise" novalidate>
			<fieldset class="sw-services">
				<legend><?php esc_html_e( '1. Choose a service', 'slotwise' ); ?></legend>
				<?php foreach ( $services as $svc ) : ?>
					<?php $dur = (int) get_post_meta( $svc->ID, '_sw_duration', true ); ?>
					<label class="sw-service">
						<input type="radio" name="service" value="<?php echo esc_attr( $svc->ID ); ?>" <?php checked( (int) $atts['service'], $svc->ID ); ?> required>
						<span><strong><?php echo esc_html( $svc->post_title ); ?></strong>
						<small><?php echo esc_html( sprintf( /* translators: %d minutes */ _n( '%d minute', '%d minutes', $dur, 'slotwise' ), $dur ) ); ?> · <?php echo esc_html( get_post_meta( $svc->ID, '_sw_price', true ) ); ?></small></span>
					</label>
				<?php endforeach; ?>
			</fieldset>

			<fieldset>
				<legend><?php esc_html_e( '2. Pick a date and time', 'slotwise' ); ?></legend>
				<label for="sw-date"><?php esc_html_e( 'Date', 'slotwise' ); ?></label>
				<input id="sw-date" type="date" name="date" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" required>
				<div class="sw-slots" role="radiogroup" aria-label="<?php esc_attr_e( 'Available times', 'slotwise' ); ?>" aria-live="polite"></div>
			</fieldset>

			<fieldset>
				<legend><?php esc_html_e( '3. Your details', 'slotwise' ); ?></legend>
				<label for="sw-name"><?php esc_html_e( 'Name', 'slotwise' ); ?></label>
				<input id="sw-name" name="name" autocomplete="name" required maxlength="80">
				<label for="sw-email"><?php esc_html_e( 'Email', 'slotwise' ); ?></label>
				<input id="sw-email" type="email" name="email" autocomplete="email" required>
				<label for="sw-phone"><?php esc_html_e( 'Phone (optional)', 'slotwise' ); ?></label>
				<input id="sw-phone" name="phone" autocomplete="tel" maxlength="30">
				<label for="sw-notes"><?php esc_html_e( 'Anything we should know? (optional)', 'slotwise' ); ?></label>
				<textarea id="sw-notes" name="notes" rows="3" maxlength="1000"></textarea>
				<div class="sw-hp" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
			</fieldset>

			<button type="submit" class="sw-submit"><?php esc_html_e( 'Confirm booking', 'slotwise' ); ?></button>
			<p class="sw-msg" role="status"></p>
		</form>
		<?php
		return ob_get_clean();
	}
}

<?php
/**
 * Admin screens: menu, settings page, service meta box and booking columns.
 *
 * @package Slotwise
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin UI.
 */
class Slotwise_Admin {

	/**
	 * Hook everything up.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'settings' ) );
		add_action( 'add_meta_boxes_sw_service', array( __CLASS__, 'meta_box' ) );
		add_action( 'save_post_sw_service', array( __CLASS__, 'save_service' ), 10, 2 );
		add_filter( 'manage_sw_booking_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_sw_booking_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_action( 'admin_post_slotwise_confirm', array( __CLASS__, 'confirm' ) );
	}

	/**
	 * Top-level menu that holds Services, Bookings and Settings.
	 */
	public static function menu() {
		add_menu_page( __( 'Slotwise', 'slotwise' ), __( 'Appointments', 'slotwise' ), 'edit_posts', 'slotwise', '__return_null', 'dashicons-calendar-alt', 26 );
		add_submenu_page( 'slotwise', __( 'Settings', 'slotwise' ), __( 'Settings', 'slotwise' ), 'manage_options', 'slotwise-settings', array( __CLASS__, 'settings_page' ) );
	}

	/**
	 * Register the option with its sanitiser.
	 */
	public static function settings() {
		register_setting(
			'slotwise',
			Slotwise_Settings::OPTION,
			array( 'sanitize_callback' => array( 'Slotwise_Settings', 'sanitize' ) )
		);
	}

	/**
	 * Settings page markup.
	 */
	public static function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s    = Slotwise_Settings::get();
		$o    = Slotwise_Settings::OPTION;
		$days = array( 1 => __( 'Mon', 'slotwise' ), 2 => __( 'Tue', 'slotwise' ), 3 => __( 'Wed', 'slotwise' ), 4 => __( 'Thu', 'slotwise' ), 5 => __( 'Fri', 'slotwise' ), 6 => __( 'Sat', 'slotwise' ), 7 => __( 'Sun', 'slotwise' ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Appointment settings', 'slotwise' ); ?></h1>
			<?php settings_errors( $o ); ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'slotwise' ); ?>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><?php esc_html_e( 'Open days', 'slotwise' ); ?></th><td>
						<?php foreach ( $days as $n => $label ) : ?>
							<label style="margin-right:1em"><input type="checkbox" name="<?php echo esc_attr( $o ); ?>[days][]" value="<?php echo esc_attr( $n ); ?>" <?php checked( in_array( $n, array_map( 'intval', $s['days'] ), true ) ); ?>> <?php echo esc_html( $label ); ?></label>
						<?php endforeach; ?>
					</td></tr>
					<tr><th scope="row"><label for="sw-open"><?php esc_html_e( 'Opening hours', 'slotwise' ); ?></label></th><td>
						<input id="sw-open" type="time" name="<?php echo esc_attr( $o ); ?>[open]" value="<?php echo esc_attr( $s['open'] ); ?>"> –
						<input type="time" aria-label="<?php esc_attr_e( 'Closing time', 'slotwise' ); ?>" name="<?php echo esc_attr( $o ); ?>[close]" value="<?php echo esc_attr( $s['close'] ); ?>">
					</td></tr>
					<tr><th scope="row"><label for="sw-int"><?php esc_html_e( 'Start times every', 'slotwise' ); ?></label></th><td>
						<select id="sw-int" name="<?php echo esc_attr( $o ); ?>[interval]">
							<?php foreach ( array( 5, 10, 15, 20, 30, 60 ) as $m ) : ?>
								<option value="<?php echo esc_attr( $m ); ?>" <?php selected( (int) $s['interval'], $m ); ?>><?php echo esc_html( $m ); ?> min</option>
							<?php endforeach; ?>
						</select>
					</td></tr>
					<tr><th scope="row"><label for="sw-buf"><?php esc_html_e( 'Buffer after each booking (min)', 'slotwise' ); ?></label></th><td><input id="sw-buf" type="number" min="0" max="120" name="<?php echo esc_attr( $o ); ?>[buffer]" value="<?php echo esc_attr( $s['buffer'] ); ?>" class="small-text"></td></tr>
					<tr><th scope="row"><label for="sw-max"><?php esc_html_e( 'Book up to (days ahead)', 'slotwise' ); ?></label></th><td><input id="sw-max" type="number" min="1" max="365" name="<?php echo esc_attr( $o ); ?>[max_days]" value="<?php echo esc_attr( $s['max_days'] ); ?>" class="small-text"></td></tr>
					<tr><th scope="row"><label for="sw-notify"><?php esc_html_e( 'Send new bookings to', 'slotwise' ); ?></label></th><td><input id="sw-notify" type="email" name="<?php echo esc_attr( $o ); ?>[notify]" value="<?php echo esc_attr( $s['notify'] ); ?>" class="regular-text"></td></tr>
				</table>
				<p><?php esc_html_e( 'Add the form to any page with the shortcode', 'slotwise' ); ?> <code>[slotwise_booking]</code>.</p>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Duration and price meta box on services.
	 */
	public static function meta_box() {
		add_meta_box( 'slotwise-service', __( 'Booking details', 'slotwise' ), array( __CLASS__, 'meta_box_html' ), 'sw_service', 'side' );
	}

	/**
	 * Meta box markup.
	 *
	 * @param WP_Post $post Service.
	 */
	public static function meta_box_html( $post ) {
		wp_nonce_field( 'slotwise_service', 'slotwise_service_nonce' );
		$dur   = (int) get_post_meta( $post->ID, '_sw_duration', true );
		$price = (string) get_post_meta( $post->ID, '_sw_price', true );
		?>
		<p><label for="sw-dur"><?php esc_html_e( 'Duration (minutes)', 'slotwise' ); ?></label><br>
		<input id="sw-dur" type="number" min="5" max="480" step="5" name="sw_duration" value="<?php echo esc_attr( $dur ? $dur : 30 ); ?>"></p>
		<p><label for="sw-price"><?php esc_html_e( 'Price label', 'slotwise' ); ?></label><br>
		<input id="sw-price" type="text" name="sw_price" value="<?php echo esc_attr( $price ); ?>" placeholder="PKR 3,000"></p>
		<?php
	}

	/**
	 * Save service meta with nonce and capability checks.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save_service( $post_id, $post ) {
		if ( ! isset( $_POST['slotwise_service_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['slotwise_service_nonce'] ) ), 'slotwise_service' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( isset( $_POST['sw_duration'] ) ) {
			update_post_meta( $post_id, '_sw_duration', max( 5, min( 480, absint( $_POST['sw_duration'] ) ) ) );
		}
		if ( isset( $_POST['sw_price'] ) ) {
			update_post_meta( $post_id, '_sw_price', sanitize_text_field( wp_unslash( $_POST['sw_price'] ) ) );
		}
	}

	/**
	 * Booking list columns.
	 *
	 * @param array $cols Columns.
	 * @return array
	 */
	public static function columns( $cols ) {
		return array(
			'cb'         => $cols['cb'],
			'title'      => __( 'Booking', 'slotwise' ),
			'sw_when'    => __( 'When', 'slotwise' ),
			'sw_contact' => __( 'Contact', 'slotwise' ),
			'sw_status'  => __( 'Status', 'slotwise' ),
		);
	}

	/**
	 * Column values.
	 *
	 * @param string $col     Column.
	 * @param int    $post_id Booking.
	 */
	public static function column( $col, $post_id ) {
		if ( 'sw_when' === $col ) {
			$date  = get_post_meta( $post_id, '_sw_date', true );
			$start = (int) get_post_meta( $post_id, '_sw_start', true );
			$end   = (int) get_post_meta( $post_id, '_sw_end', true );
			echo esc_html( $date . ' ' . Slotwise_Availability::to_hm( $start ) . '–' . Slotwise_Availability::to_hm( $end ) );
		} elseif ( 'sw_contact' === $col ) {
			$email = get_post_meta( $post_id, '_sw_email', true );
			echo '<a href="' . esc_url( 'mailto:' . $email ) . '">' . esc_html( $email ) . '</a><br>' . esc_html( get_post_meta( $post_id, '_sw_phone', true ) );
		} elseif ( 'sw_status' === $col ) {
			echo 'publish' === get_post_status( $post_id ) ? esc_html__( 'Confirmed', 'slotwise' ) : esc_html__( 'Awaiting confirmation', 'slotwise' );
		}
	}

	/**
	 * One-click "Confirm" link on pending bookings.
	 *
	 * @param array   $actions Row actions.
	 * @param WP_Post $post    Post.
	 * @return array
	 */
	public static function row_actions( $actions, $post ) {
		if ( 'sw_booking' === $post->post_type && 'pending' === $post->post_status && current_user_can( 'edit_post', $post->ID ) ) {
			$url                  = wp_nonce_url( admin_url( 'admin-post.php?action=slotwise_confirm&booking=' . $post->ID ), 'slotwise_confirm_' . $post->ID );
			$actions['sw_confirm'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Confirm', 'slotwise' ) . '</a>';
		}
		return $actions;
	}

	/**
	 * Handle the Confirm link.
	 */
	public static function confirm() {
		$id = isset( $_GET['booking'] ) ? absint( $_GET['booking'] ) : 0;
		check_admin_referer( 'slotwise_confirm_' . $id );
		if ( ! $id || 'sw_booking' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
			wp_die( esc_html__( 'You cannot confirm this booking.', 'slotwise' ) );
		}
		wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
		wp_safe_redirect( admin_url( 'edit.php?post_type=sw_booking' ) );
		exit;
	}
}

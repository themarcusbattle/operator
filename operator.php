<?php
/**
 * Plugin Name: Operator
 * Plugin Author: Marcus Battle
 * Version: 0.1.0
 * Description: Programmable wireless number system.
 * Domain: operator
 */

 class Operator {

	/**
	 * Plugin class
	 *
	 * @var   Operator
	 * @since 0.1.0
	 */
	protected static $single_instance = null;

	/**
	 * Create an instance of the Operator object
	 *
	 * @since 0.1.0
	 */
	static function init() {

		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;

	}

	public function hooks() {

		// Global hooks.
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'rest_api_init', array( $this, 'register_api_endpoints' ) );

		// Admin hooks.
		add_action( 'admin_menu', array( $this, 'add_menu_page' ), 10, 1 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function register_post_types() {

		$args = array(
			'public'    => true,
			'label'     => 'Messages',
			'menu_icon' => 'dashicons-format-chat',
		);

		register_post_type( 'message', $args );
	}

	public function add_menu_page() {
		add_management_page( __( 'Operator','operator' ), __( 'Operator','operator' ), 'manage_options', 'operator', array( $this, 'show_menu_page' ) );
	}

	public function show_menu_page() {
		?>
			<div class="wrap">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<form class="form" method="post" action="options.php">
					<?php settings_fields( 'operator' ); ?>
					<?php do_settings_sections( 'operator' ); ?>
					<table class="form-table">
						<tr valign="top">
							<th scope="row">Provider Account ID</th>
							<td>
								<input type="text" name="op_provider_account_id" value="<?php echo esc_attr( get_option( 'op_provider_account_id' ) ); ?>" />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">Provider Token</th>
							<td>
								<input type="text" name="op_provider_token" value="<?php echo esc_attr( get_option( 'op_provider_token' ) ); ?>" />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">Provider Secret</th>
							<td>
								<input type="text" name="op_provider_secret" value="<?php echo esc_attr( get_option( 'op_provider_secret' ) ); ?>" />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">Phone Number</th>
							<td>
								<input type="text" name="op_phone_number" value="<?php echo esc_attr( get_option( 'op_phone_number' ) ); ?>" />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">Forwarding Number</th>
							<td>
								<input type="text" name="op_forwarding_number" value="<?php echo esc_attr( get_option( 'op_forwarding_number' ) ); ?>" />
							</td>
						</tr>
					</table>
					<?php submit_button(); ?>
				</form>
			</div>
		<?php
	}

	public function register_settings() {
		register_setting( 'operator', 'op_provider_account_id' );
		register_setting( 'operator', 'op_provider_token' );
		register_setting( 'operator', 'op_provider_secret' );
		register_setting( 'operator', 'op_phone_number' );
		register_setting( 'operator', 'op_forwarding_number' );
	}

	/**
	 * Register the api endpoints
	 *
	 * @since 0.1.0
	 */
	public function register_api_endpoints() {

		register_rest_route( 'operator/v1', '/sms', array(
			'methods' => array( 'GET', 'POST' ),
			'callback' => array( $this, 'capture_sms' ),
			// 'permission_callback' => array( $this, 'validate_sync_key' ),
		) );

	}

	public function capture_sms( $request ) {

		// Check to see if the eventType is an SMS request.
		if ( 'sms' !== $request->get_param( 'eventType' ) ) {
			return false;
		}

		// Capture all of the request parameters.
		$message = $request->get_param( 'text' );
		$from    = $request->get_param( 'from' );

		$message_args = array(
			'post_type'    => 'message',
			'post_status'  => 'publish',
			'post_content' => $message,
			'post_date'    => $request->get_param( 'time' ),
			'meta_input'   => array(
				'type'        => $request->get_param( 'eventType' ),
				'direction'   => $request->get_param( 'direction' ),
				'from_number' => $from,
				'to_number'   => $request->get_param( 'to' ),
				'message_id'  => $request->get_param( 'messageId' ),
				'message_uri' => $request->get_param( 'messageUri' ),
				'status'      => $request->get_param( 'state' ),
			),
		);

		$message_id = wp_insert_post( $message_args );

		$this->forward_sms_to_phone( $from, $message );
	}

	public function forward_sms_to_phone( $from = '', $message = '' ) {

		$provider_account_id = get_option( 'op_provider_account_id' );
		$provider_token      = get_option( 'op_provider_token' );
		$provider_secret     = get_option( 'op_provider_secret' );
		$phone_number        = get_option( 'op_phone_number' );
		$url                 = "https://api.catapult.inetwork.com/v1/users/{$provider_account_id}/messages";

		if ( ! $forwarding_number = get_option( 'op_forwarding_number' ) ) {
			return false;
		}

		$message = "$from: $message";

		$response = wp_remote_post( $url, array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $provider_token . ':' . $provider_secret ),
				'Content-type'  => 'application/json',
			),
			'body' => wp_json_encode( array(
				'from' => $phone_number,
				'to'   => $forwarding_number,
				'text' => $message,
			) ),
		) );

		return wp_remote_retrieve_body( $response );
	}
}

add_action( 'plugins_loaded', array( Operator::init(), 'hooks' ), 10, 1 );

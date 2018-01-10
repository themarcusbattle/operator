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
					<?php submit_button(); ?>
				</form>
			</div>
		<?php
	}

	/**
	 * Register the api endpoints
	 *
	 * @since 0.1.0
	 */
	public function register_api_endpoints() {

		register_rest_route( 'operator/v1', '/sms', array(
			// 'methods' => array( 'GET', 'POST' ),
			'methods' => array( 'GET' ),
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
		$message_args = array(
			'post_type'    => 'message',
			'post_status'  => 'publish',
			'post_content' => $request->get_param( 'text' ),
			'post_date'    => $request->get_param( 'time' ),
			'meta_input'   => array(
				'type'        => $request->get_param( 'eventType' ),
				'direction'   => $request->get_param( 'direction' ),
				'from_number' => $request->get_param( 'from' ),
				'to_number'   => $request->get_param( 'to' ),
				'message_id'  => $request->get_param( 'messageId' ),
				'message_uri' => $request->get_param( 'messageUri' ),
				'status'      => $request->get_param( 'state' ),
			),
		);

		$message_id = wp_insert_post( $message_args );
		
		print_r( $message_id ); exit;
	}
}

add_action( 'plugins_loaded', array( Operator::init(), 'hooks' ), 10, 1 );

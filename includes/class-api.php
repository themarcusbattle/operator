<?php

class Nock_API {

	/**
	 * Parent plugin class.
	 *
	 * @since 0.1.0
	 *
	 * @var Nock
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  0.1.0
	 *
	 * @param  Nock $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since 0.1.0
	 */
	public function hooks() {
		add_action( 'rest_api_init', array( $this, 'register_api_endpoints' ) );
	}

	/**
	 * Register the api endpoints
	 *
	 * @since 0.1.0
	 */
	public function register_api_endpoints() {

		register_rest_route( 'nock/v1', '/messages', array(
			'methods' => array( 'POST' ),
			'callback' => array( $this, 'send_message' ),
		) );

		register_rest_route( 'nock/v1', '/accounts', array(
			'methods' => array( 'GET' ),
			'callback' => array( $this, 'get_accounts' ),
		) );

		register_rest_route( 'nock/v1', '/groups', array(
			'methods' => array( 'GET' ),
			'callback' => array( $this, 'get_groups' ),
		) );

		register_rest_route( 'nock/v1', '/sms', array(
			'methods' => array( 'GET', 'POST' ),
			'callback' => array( $this, 'capture_sms' ),
		) );

	}

	public function send_message( $request ) {

		$message        = $request->get_param( 'message' );
		$account_id     = $request->get_param( 'account_id' );
		$group_ids      = array( $request->get_param( 'group_id' ) );
		$subscriber_ids = array( $request->get_param( 'subscriber_id' ) );

		// Create the message.
		$message_id = $this->plugin->messages->create( $message, $account_id );

		if ( is_wp_error( $message_id ) || ! $message_id ) {
			wp_send_json_error();
		}

		// Send the message to groups.
		$this->plugin->messages->attach_groups( $message_id, $group_ids );

		// Send the message to any subscribers.
		$this->plugin->messages->attach_subscribers( $message_id, $subscriber_ids );

		$group_subscribers = $this->plugin->groups->get_group_subscribers( $group_ids );
		$group_subscribers = ! empty( $group_subscribers ) ? array_column( $group_subscribers, 'user_id' ) : array();

		// Consolidate all subscriber ids to parse the phone numbers from.
		$subscriber_ids = ( ! empty( $subscriber_ids ) && ! empty( $group_subscribers ) ) ? array_merge( $subscriber_ids, $group_subscribers ) : $group_subscribers;
		$subscriber_ids = $subscriber_ids ? array_values( array_filter( $subscriber_ids ) ) : array();

		foreach ( $subscriber_ids as $subscriber_id ) {
			$number = $this->plugin->subscribers->get_number( $subscriber_id );
			$this->forward_message_to_phone( $number, $message, $account_id );
		}

		wp_send_json_success( $subscriber_ids );
	}

	public function get_accounts( $request ) {

		$account_id = $request->get_param( 'account_id' );
		$accounts   = $this->plugin->accounts->get( $account_id );

		wp_send_json_success( $accounts );
	}

	public function get_groups( $request ) {

		$group_id   = $request->get_param( 'group_id' );
		$account_id = $request->get_param( 'account_id' );
		$groups     = $this->plugin->groups->get( $group_id, $account_id );

		wp_send_json_success( $groups );
	}

	public function capture_sms( $request ) {

		// Check to see if the eventType is an SMS request.
		if ( 'sms' !== $request->get_param( 'eventType' ) ) {
			wp_send_json_error();
		}

		// Capture all of the request parameters.
		$message = $request->get_param( 'text' );
		$from    = str_replace( '+', '', $request->get_param( 'from' ) );
		$to      = str_replace( '+', '', $request->get_param( 'to' ) );

		// Find the account.
		$account = $this->plugin->accounts->get_by_number( $to );

		// Stop if we can't find an account.
		if ( ! $account ) {
			wp_send_json_error();
		}

		// Stop if the message is blank.
		if ( empty( $message ) ) {
			wp_send_json_error();
		}

		$subscriber_id = $this->plugin->subscribers->get_id_by_number( $from );

		// Create the subscriber if they don't exist.
		if ( ! $subscriber_id ) {
			$subscriber_id = $this->plugin->subscribers->create( $from, $account['id'] );
		}

		// Create the message.
		$message_id = $this->plugin->messages->create( $message, $account['id'], 'incoming' );

		// Attach the sender to the message.
		$message_groups = $this->plugin->messages->attach_subscribers( $message_id, array( $subscriber_id ) );

		wp_send_json_success( $message_groups ); 

		$keyword_ids = get_post_meta( $nock_number->ID, 'keywords', true );
		$keywords    = array();

		foreach ( $keyword_ids as $keyword_id ) {
			$keywords[] = strtolower( get_the_title( $keyword_id ) );
		}

		$first_word = $this->get_first_word_of_sms( $message );

		if ( ! in_array( $first_word, $keywords ) ) {
			return false;
		}

		echo "Add the user if they don't already exist";
		exit;
		$message_args = array(
			'post_type'    => 'message',
			'post_status'  => 'publish',
			'post_content' => $message,
			'post_date'    => $request->get_param( 'time' ),
			'meta_input'   => array(
				'type'        => $request->get_param( 'eventType' ),
				'direction'   => $request->get_param( 'direction' ),
				'from_number' => $from,
				'to_number'   => $to,
				'message_id'  => $request->get_param( 'messageId' ),
				'message_uri' => $request->get_param( 'messageUri' ),
				'status'      => $request->get_param( 'state' ),
			),
		);

		$message_id = wp_insert_post( $message_args );

		if ( get_option( 'op_forwarding_number' ) === $from ) {
			$this->reply_to_sms( $message );
		} else {
			$this->forward_sms_to_phone( $from, $message );
		}
	}

	public function get_first_word_of_sms( $sms = '' ) {

		if ( empty( $sms ) ) {
			return '';
		}

		$words = explode( ' ', $sms );

		return $words[0];
	}

	public function reply_to_sms( $message = '' ) {

		if ( empty( $message ) ) {
			return false;
		}

		$words = explode( ' ', $message );
		$to    = absint( $words[0] );

		if ( ! $to ) {
			return false;
		}

		$message = str_replace( $to, '', $message );

		$provider_account_id = get_option( 'op_provider_account_id' );
		$provider_token      = get_option( 'op_provider_token' );
		$provider_secret     = get_option( 'op_provider_secret' );
		$phone_number        = get_option( 'op_phone_number' );
		$url                 = "https://api.catapult.inetwork.com/v1/users/{$provider_account_id}/messages";

		if ( ! $forwarding_number = get_option( 'op_forwarding_number' ) ) {
			return false;
		}

		$response = wp_remote_post( $url, array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $provider_token . ':' . $provider_secret ),
				'Content-type'  => 'application/json',
			),
			'body' => wp_json_encode( array(
				'from' => $phone_number,
				'to'   => $to,
				'text' => $message,
			) ),
		) );

		return wp_remote_retrieve_body( $response );
	}

	public function forward_message_to_phone( $number = '', $message = '', $account_id = 0 ) {

		$provider_account_id = get_option( 'op_provider_account_id' );
		$provider_token      = get_option( 'op_provider_token' );
		$provider_secret     = get_option( 'op_provider_secret' );
		$phone_number        = get_option( 'op_phone_number' );
		$url                 = "https://api.catapult.inetwork.com/v1/users/{$provider_account_id}/messages";

		// Get the account
		$accounts = $this->plugin->accounts->get( $account_id );
		$account  = isset( $accounts[0] ) ? $accounts[0] : array();

		if ( empty( $account ) ) {
			return false;
		}

		$message = "{$account['name']}: $message";

		$response = wp_remote_post( $url, array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $provider_token . ':' . $provider_secret ),
				'Content-type'  => 'application/json',
			),
			'body' => wp_json_encode( array(
				'from' => $account['number'],
				'to'   => $number,
				'text' => $message,
			) ),
		) );

		return wp_remote_retrieve_body( $response );
	}
}

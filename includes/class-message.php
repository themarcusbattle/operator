<?php

class Nock_Message {

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
		$this->install_db();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since 0.1.0
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ), 10, 1 );
	}

	public function install_db () {

		global $wpdb;

		$table_name = $wpdb->prefix . 'nock_messages';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			content tinytext NOT NULL,
			user_id mediumint(9) NOT NULL,
			group_id mediumint(9) NOT NULL,
			account_id mediumint(9) NOT NULL,
			direction varchar(8) NOT NULL,
			created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	public function add_submenu_page() {
		add_menu_page( 
			__( 'Nock','nock' ),
			'Nock',
			'manage_options',
			'nock',
			null,
			'dashicons-email-alt'
		);

		add_submenu_page(
			'nock',
			__( 'Send Message', 'nock' ),
			'Send Message',
			'manage_options',
			'nock',
			array( $this, 'display_messages_page' )
		);
	}

	public function display_messages_page() {
		?>
			<div class="wrap">
				<h2>Send Message</h2>
				<p>This screen will allow you to send one message to your entire network.</p>
				<form id="message-editor" style="width: 600px;">
					<textarea name="message" rows="5" style="width: 600px; padding: 15px;"></textarea>
					<label>Account</label>
					<?php $accounts = apply_filters( 'nock_accounts', array() ); ?>
					<select name="account_id">
						<option>--</option>
						<?php foreach ( $accounts as $account ) : ?>
							<option value="<?php echo $account['id']; ?>"><?php echo esc_attr( $account['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
					<label>Group</label>
					<select id="select-group" name="group_id">
						<option>--</option>
					</select>
					<button class="button button-primary send-message-button">Send Message</button>
				</form>
			</div>
		<?php
	}

	/**
	 * Record a new message to the database.
	 *
	 * @since 0.1.0
	 *
	 * @param String  $message    The message content.
	 * @param Integer $account_id The account ID.
	 *
	 * @return Integer
	 */
	public function create( $message = '', $account_id = 0, $direction = 'outgoing' ) {

		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'nock_messages',
			array(
				'content'    => sanitize_text_field( $message ),
				'account_id' => absint( $account_id ),
				'direction'  => sanitize_text_field( $direction ),
			),
			array(
				'%s',
				'%d',
				'%s',
			)
		);

		return $wpdb->insert_id;
	}

	/**
	 * Attaches groups as recipients of a message.
	 *
	 * @since 0.1.0
	 *
	 * @param Integer $message_id The message id.
	 * @param Array   $group_ids  The groups that will receive the message.
	 */
	public function attach_groups( $message_id, $group_ids ) {

		global $wpdb;

		$message_groups = array();

		if ( ! $message_id || empty( $group_ids ) ) {
			return $message_groups;
		}

		foreach ( $group_ids as $group_id ) {

			$wpdb->insert(
				$wpdb->prefix . 'nock_message_groups',
				array(
					'message_id' => absint( $message_id ),
					'group_id'   => absint( $group_id ),
				),
				array(
					'%d',
					'%d',
				)
			);

			$message_groups[] = $wpdb->insert_id;
		}

		return $message_groups;
	}

	/**
	 * Attaches subscribers as recipients or senders of a message.
	 *
	 * @since 0.1.0
	 *
	 * @param Integer $message_id     The message id.
	 * @param Array   $subscriber_ids The groups that will receive the message.
	 */
	public function attach_subscribers( $message_id, $subscriber_ids ) {

		global $wpdb;

		$message_subscribers = array();

		if ( ! $message_id || empty( $subscriber_ids ) ) {
			return $message_subscribers;
		}

		foreach ( $subscriber_ids as $subscriber_id ) {

			$wpdb->insert(
				$wpdb->prefix . 'nock_message_subscribers',
				array(
					'message_id' => absint( $message_id ),
					'subscriber_id'   => absint( $subscriber_id ),
				),
				array(
					'%d',
					'%d',
				)
			);

			$message_subscribers[] = $wpdb->insert_id;
		}

		return $message_subscribers;
	}
}

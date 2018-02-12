<?php

class Nock_Subscriber {

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
		$this->install_message_subscribers_db();
	}

	public function install_message_subscribers_db() {

		global $wpdb;

		$table_name = $wpdb->prefix . 'nock_message_subscribers';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			message_id mediumint(9) NOT NULL,
			subscriber_id mediumint(9) NOT NULL,
			created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since 0.1.0
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'add_page' ), 10, 1 );
		add_filter( 'nock_subscribers', array( $this, 'get' ), 10, 1 );
	}

	/**
	 * Initialize the 'Accounts' page.
	 */
	public function add_page() {
		add_submenu_page(
			'nock',
			__( 'Accounts', 'nock' ),
			'Subscribers',
			'manage_options',
			'subscribers',
			array( $this, 'show_page' )
		);
	}

	public function show_page() {

		$data = apply_filters( 'nock_subscribers', array() );

		$columns = array(
			'name'              => 'Name',
			'mobile_number'     => 'Number',
			'subscriber_groups' => 'Groups',
			'message_count'     => 'Messages',
		);

		$race_table = new Nock_Data_Table( array( 'singular' => 'Group', 'plural' => 'Groups' ), $columns, $this->plugin );
		$race_table->prepare_items( $data );
		$race_table->display();
	}

	public function get( $groups = array(), $get_details = true ) {

		global $wpdb;

		$sql = "SELECT accounts.* FROM {$wpdb->prefix}nock_accounts AS accounts LEFT JOIN {$wpdb->prefix}nock_groups AS groups ON groups.account_id = accounts.id";

		$groups = $wpdb->get_results( "SELECT groups.* FROM {$wpdb->prefix}nock_groups AS groups LEFT JOIN {$wpdb->prefix}nock_groups AS groups ON groups.account_id = accounts.id", ARRAY_A );

		return $groups;
	}

	/**
	 * Create a new subscriber.
	 *
	 * @since 0.1.0
	 *
	 * @param String  $number     The subscriber's mobile number.
	 * @param Integer $account_id The account id.
	 */
	public function create( $number = '', $account_id = 0 ) {
		echo 'awesome';
	}

	/**
	 * Find a user by their mobile number.
	 *
	 * @since 0.1.0
	 *
	 * @param String $number The user's mobile number.
	 */
	public function get_id_by_number( $number = '' ) {

		global $wpdb;

		$sql = "SELECT user_id FROM {$wpdb->prefix}usermeta WHERE meta_key = 'mobile_number' AND meta_value='{$number}'";

		return $wpdb->get_var( $sql );
	}

	/**
	 * Get the mobile number of the subscriber.
	 *
	 * @since 0.1.0
	 *
	 * @param Integer $subscriber_id The Subcriber's id.
	 */
	public function get_number( $subscriber_id = 0 ) {
		return get_user_meta( $subscriber_id, 'mobile_number', true );
	}
}

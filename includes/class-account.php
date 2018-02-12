<?php

class Nock_Account {

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

	public function install_db () {

		global $wpdb;

		$table_name = $wpdb->prefix . 'nock_accounts';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name tinytext NOT NULL,
			number tinytext NOT NULL,
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
		add_action( 'admin_menu', array( $this, 'add_accounts_page' ), 10, 1 );
		add_filter( 'nock_accounts', array( $this, 'get_accounts' ), 10, 1 );
	}

	/**
	 * Initialize the 'Accounts' page.
	 */
	public function add_accounts_page() {
		add_submenu_page(
			'nock',
			__( 'Accounts', 'nock' ),
			'Accounts',
			'manage_options',
			'accounts',
			array( $this, 'show_page' )
		);
	}

	/**
	 * The get method.
	 *
	 * @since 0.1.0
	 *
	 * @param Integer $account_id The account ID.
	 *
	 * @return Array  $accounts
	 */
	public function get( $account_id = 0 ) {

		global $wpdb;

		$accounts = array();

		if ( $account_id ) {
			$where = "WHERE accounts.id = $account_id";
		}

		$sql =
		"
		SELECT 
			accounts.*,
			COUNT(DISTINCT subscribers.id) AS subscriber_count,
			COUNT(DISTINCT groups.id) AS group_count,
			COUNT(DISTINCT messages.id) AS message_count
		FROM {$wpdb->prefix}nock_accounts AS accounts
			LEFT JOIN {$wpdb->prefix}nock_subscribers AS subscribers ON subscribers.account_id = accounts.id
			LEFT JOIN {$wpdb->prefix}nock_groups AS groups ON groups.account_id = accounts.id
			LEFT JOIN {$wpdb->prefix}nock_messages AS messages ON messages.account_id = accounts.id
		{$where}
		GROUP BY accounts.id
		";

		$accounts = $wpdb->get_results( $sql, ARRAY_A );

		return $accounts;
	}

	public function get_accounts( $accounts = array() ) { 
		return $this->get();
	}

	public function get_by_number( $number = '' ) {

		global $wpdb;

		$sql = "
			SELECT * FROM {$wpdb->prefix}nock_accounts AS accounts WHERE number = '{$number}'
		";

		$account = $wpdb->get_results( $sql, ARRAY_A );
		$account = isset( $account[0] ) ? $account[0] : array();

		return $account;
	}

	public function show_page() {

		$data = apply_filters( 'nock_accounts', array() );

		$columns = array(
			'name'              => 'Name',
			'number'            => 'Number',
			'forwarding_number' => 'Forwarding Number',
			'subscriber_count'  => 'Subscribers',
			'group_count'       => 'Groups',
			'message_count'     => 'Messages',
		);

		$race_table = new Nock_Data_Table( array( 'singular' => 'Account', 'plural' => 'Accounts' ), $columns, $this->plugin );
		$race_table->prepare_items( $data );
		$race_table->display();
	}

}

<?php

class Nock_Group {

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
		$this->install_group_subscribers_db();
		$this->install_message_groups_db();
	}

	public function install_db() {

		global $wpdb;

		$table_name = $wpdb->prefix . 'nock_groups';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name tinytext NOT NULL,
			account_id mediumint(9) NOT NULL,
			created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	public function install_group_subscribers_db() {

		global $wpdb;

		$table_name = $wpdb->prefix . 'nock_group_subscribers';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			group_id mediumint(9) NOT NULL,
			user_id mediumint(9) NOT NULL,
			created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	public function install_message_groups_db() {

		global $wpdb;

		$table_name = $wpdb->prefix . 'nock_message_groups';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			message_id mediumint(9) NOT NULL,
			group_id mediumint(9) NOT NULL,
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
		add_filter( 'nock_groups', array( $this, 'get' ), 10, 1 );
	}

	/**
	 * Initialize the 'Accounts' page.
	 */
	public function add_page() {
		add_submenu_page(
			'nock',
			__( 'Accounts', 'nock' ),
			'Groups',
			'manage_options',
			'groups',
			array( $this, 'show_page' )
		);
	}

	public function get( $group_id = 0, $account_id = 0 ) {

		global $wpdb;

		$where = '';

		if ( $group_id && $account_id ) {
			$where = "WHERE groups.id = $group_id AND accounts.id = $account_id";
		} else if ( $group_id ) {
			$where = "WHERE groups.id = $group_id";
		} else if ( $account_id ) {
			$where = "WHERE accounts.id = $account_id";
		}

		$sql = "
			SELECT 
				groups.*,
				accounts.name AS account,
				COUNT(subscribers.id) AS subscriber_count,
				COUNT(message_groups.id) AS message_count
			FROM {$wpdb->prefix}nock_groups AS groups 
				LEFT JOIN {$wpdb->prefix}nock_accounts AS accounts ON groups.account_id = accounts.id
				LEFT JOIN {$wpdb->prefix}nock_group_subscribers AS subscribers ON subscribers.group_id = groups.id
				LEFT JOIN {$wpdb->prefix}nock_message_groups AS message_groups ON message_groups.group_id = groups.id
			{$where}
			GROUP BY groups.id
			";

		$groups = $wpdb->get_results( $sql, ARRAY_A );

		return $groups;
	}

	public function get_groups( $groups = array() ) {
		return $this->get();
	}

	public function show_page() {

		$data = apply_filters( 'nock_groups', array() );

		$columns = array(
			'name'              => 'Name',
			'account'           => 'Account',
			'subscriber_count'  => 'Subscribers',
			'message_count'     => 'Messages',
		);

		$race_table = new Nock_Data_Table( array( 'singular' => 'Group', 'plural' => 'Groups' ), $columns, $this->plugin );
		$race_table->prepare_items( $data );
		$race_table->display();
	}

	public function get_group_subscribers( $group_ids = array() ) {

		global $wpdb;

		$group_subscribers = array();

		if ( empty( $group_ids ) ) {
			return $group_subscribers;
		}

		$group_ids = implode( "','", $group_ids );

		$sql = "SELECT user_id FROM {$wpdb->prefix}nock_group_subscribers WHERE group_id IN ('{$group_ids}')";

		return $wpdb->get_results( $sql );
	}
}

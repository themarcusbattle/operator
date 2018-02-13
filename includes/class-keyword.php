<?php

class Nock_Keyword {

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
	 * @param  Nock_Keyword $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
		$this->install_keywords_db();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since 0.1.0
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'add_page' ), 10, 1 );
	}

	public function install_keywords_db() {

		global $wpdb;

		$table_name = $wpdb->prefix . 'nock_keywords';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			keyword mediumint(9) NOT NULL,
			group_id mediumint(9) NOT NULL,
			created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Initialize the 'Keywords' page.
	 */
	public function add_page() {
		add_submenu_page(
			'nock',
			__( 'Keywords', 'nock' ),
			'Keywords',
			'manage_options',
			'keywords',
			array( $this, 'show_page' )
		);
	}

	public function show_page() {

		$data = apply_filters( 'nock_keywords', array() );

		$columns = array(
			'name'             => 'Keyword',
			'group'            => 'Group',
			'account'          => 'Account',
			'subscriber_count' => 'Subscribers',
		);

		$race_table = new Nock_Data_Table( array( 'singular' => 'Keyword', 'plural' => 'Keywords' ), $columns, $this->plugin );
		$race_table->prepare_items( $data );
		$race_table->display();
	}
}

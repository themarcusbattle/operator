<?php
/**
 * RunSignUp API Importer Race Table.
 *
 * @since   0.1.0
 * @package RunSignUp_API_Importer
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * RunSignUp API Importer Race Table.
 *
 * @since 0.1.0
 */
class Nock_Data_Table extends WP_List_Table {
	/**
	 * Parent plugin class.
	 *
	 * @since 0.1.0
	 *
	 * @var   RunSignUp_API_Importer
	 */
	protected $plugin = null;

	/**
	 * The columns to display in the table.
	 *
	 * @since 0.1.0
	 *
	 * @var array
	 */
	protected $columns = array();

	/**
	 * Constructor.
	 *
	 * @since  0.1.0
	 *
	 * @param array                  $args    The arguments to define the table.
	 * @param array                  $columns The columns to display in the table.
	 * @param RunSignUp_API_Importer $plugin  Main plugin object.
	 */
	public function __construct( $args = array(), $columns = array(), $plugin = null ) {

		$this->plugin  = $plugin;
		$this->columns = $columns;

		// Set parent defaults.
		parent::__construct( wp_parse_args( $args, array(
			'singular' => 'Table Row',
			'plural'   => 'Table Rows',
			'ajax'     => true,
		) ) );
	}

	/**
	 * Prepare the items for the table to process
	 *
	 * @return Void
	 */
	public function prepare_items( $data = array() ) {

		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$per_page     = 10;
		$current_page = $this->get_pagenum();
		$total_items  = count( $data );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
		) );

		if ( $data ) {
			$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
		}

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items = $data;
	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table
	 *
	 * @return Array
	 */
	public function get_columns() {
		return $this->columns;
	}

	/**
	 * Define which columns are hidden
	 *
	 * @return Array
	 */
	public function get_hidden_columns() {
		return array();
	}

	/**
	 * Define the sortable columns
	 *
	 * @return Array
	 */
	public function get_sortable_columns() {
		return array( 'title' => array( 'title', false ) );
	}

	/**
	 * Define what data to show on each column of the table
	 *
	 * @param  Array $item        Data
	 * @param  String $column_name - Current column name
	 *
	 * @return Mixed
	 */
	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {
			default:
				return esc_attr( $item[ $column_name ] );
		}
	}

	/**
	 * Extracts the distances from the individual race events
	 *
	 * @since 0.1.0
	 *
	 * @param array $events An array of events.
	 *
	 * @return string $distances
	 */
	public function parse_distances( $events = array() ) {

		$distances = array();

		if ( empty( $events ) ) {
			return '';
		}

		foreach ( $events as $event ) {
			$distances[] = $event['distance'];
		}

		return implode( ',', array_filter( $distances ) );
	}

	/**
	 * Converts the address into a string.
	 *
	 * @since 0.1.0
	 *
	 * @return string $address;
	 */
	public function parse_address( $address = array() ) {

		if ( empty( $address ) ) {
			return $address;
		}

		return $address['city'] . ', ' . $address['state'];
	}
}

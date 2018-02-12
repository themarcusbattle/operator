<?php

class Nock_User {

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
		add_action( 'init', array( $this, 'register_group_taxonomy' ) );
		add_action( 'admin_menu', array( $this, 'add_group_page' ) );

		/* Add section to the edit user page in the admin to select profession. */
		add_action( 'show_user_profile', array( $this, 'add_group_section_to_profile' ) );
		add_action( 'edit_user_profile', array( $this, 'add_group_section_to_profile' ) );

		/* Update the profession terms when the edit user page is updated. */
		add_action( 'personal_options_update', array( $this, 'save_user_groups_from_profile' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_user_groups_from_profile' ) );
	}

	public function install_db () {

		global $wpdb;

		$table_name = $wpdb->prefix . 'nock_subscribers';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			account_id mediumint(9) NOT NULL,
			user_id mediumint(9) NOT NULL,
			created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Register the 'Keyword' taxonomy.
	 *
	 * @since 0.1.0
	 */
	public function register_group_taxonomy() {

		register_taxonomy(
			'group',
			'user',
			array(
				'label'        => __( 'Groups' ),
				'public'       => true,
				'rewrite'      => array( 'slug' => 'groups' ),
				'hierarchical' => true,
			)
		);
	}

	/**
	 * Adds the group page.
	 *
	 * @since 0.1.0
	 */
	public function add_group_page() {

		$tax = get_taxonomy( 'group' );

		add_users_page(
			esc_attr( $tax->labels->menu_name ),
			esc_attr( $tax->labels->menu_name ),
			$tax->cap->manage_terms,
			'edit-tags.php?taxonomy=' . $tax->name
		);
	}

	/**
	 * Adds an additional settings section on the edit user/profile page in the admin.  This section allows users to 
	 * select a profession from a checkbox of terms from the profession taxonomy.  This is just one example of 
	 * many ways this can be handled.
	 *
	 * @param object $user The user object currently being edited.
	 */
	public function add_group_section_to_profile( $user ) {

		$tax = get_taxonomy( 'group' );

		/* Make sure the user can assign terms of the profession taxonomy before proceeding. */
		if ( !current_user_can( $tax->cap->assign_terms ) )
			return;

		/* Get the terms of the 'profession' taxonomy. */
		$terms = get_terms( 'group', array( 'hide_empty' => false ) ); ?>

		<h3><?php _e( 'Group' ); ?></h3>

		<table class="form-table">

			<tr>
				<th><label for="group"><?php _e( 'Select Group' ); ?></label></th>

				<td><?php

				/* If there are any profession terms, loop through them and display checkboxes. */
				if ( !empty( $terms ) ) {

					foreach ( $terms as $term ) { ?>
						<input type="checkbox" name="group[]" id="group-<?php echo esc_attr( $term->slug ); ?>" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( true, is_object_in_term( $user->ID, 'group', $term ) ); ?> /> <label for="group-<?php echo esc_attr( $term->slug ); ?>"><?php echo $term->name; ?></label> <br />
					<?php }
				}

				/* If there are no profession terms, display a message. */
				else {
					_e( 'There are no groups available.' );
				}

				?></td>
			</tr>

		</table>
	<?php
	}

	public function save_user_groups_from_profile( $user_id ) {

		$tax = get_taxonomy( 'profession' );

		/* Make sure the current user can edit the user and assign terms before proceeding. */
		if ( ! current_user_can( 'edit_user', $user_id ) && current_user_can( $tax->cap->assign_terms ) )
			return false;

		$term = esc_attr( $_POST['group'] );

		/* Sets the terms (we're just using a single term) for the user. */
		wp_set_object_terms( $user_id, array( $term ), 'group', false);

		clean_object_term_cache( $user_id, 'group' );
	}
}

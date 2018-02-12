<?php

class Nock_Number {

	/**
	 * Parent plugin class.
	 *
	 * @since 0.1.0
	 *
	 * @var Nock_Number
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
		add_action( 'init', array( $this, 'register_number_post_type' ) );
	}

	/**
	 * Register the 'Phone Number' CPT.
	 *
	 * @since 0.1.0
	 */
	public function register_number_post_type() {

		$args = array(
			'public'    => true,
			'label'     => 'Numbers',
			'menu_icon' => 'dashicons-phone',
		);

		register_post_type( 'number', $args );
	}
}

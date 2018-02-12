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
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since 0.1.0
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'register_keyword_taxonomy' ) );
		add_action( 'init', array( $this, 'register_keyword_post_type' ) );
	}

	/**
	 * Register the 'Keyword' taxonomy.
	 *
	 * @since 0.1.0
	 */
	public function register_keyword_taxonomy() {

		register_taxonomy(
			'keyword',
			'number',
			array(
				'label'        => __( 'Keywords' ),
				'public'       => true,
				'rewrite'      => array( 'slug' => 'keywords' ),
				'hierarchical' => false,
			)
		);
	}

	/**
	 * Register the 'Keyword' CPT.
	 *
	 * @since 0.1.0
	 */
	public function register_keyword_post_type() {

		$args = array(
			'public'    => true,
			'label'     => 'Keywords',
			'menu_icon' => 'dashicons-format-chat',
		);

		register_post_type( 'keyword', $args );
	}
}

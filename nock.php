<?php
/**
 * Plugin Name: Nock
 * Plugin Author: Marcus Battle
 * Version: 0.1.0
 * Description: A private social network built with Bandwith™ and WordPress
 * Domain: nock
 */

/**
 * Autoloads files with classes when needed.
 *
 * @since 0.1.0
 * @param string $class_name Name of the class being requested.
 */
function nock_autoload_classes( $class_name ) {

	// If our class doesn't have our prefix, don't load it.
	if ( 0 !== strpos( $class_name, 'Nock_' ) ) {
		return;
	}

	// Set up our filename.
	$filename = strtolower( str_replace( '_', '-', substr( $class_name, strlen( 'Nock_' ) ) ) );

	// Include our file.
	Nock::include_file( 'includes/class-' . $filename );
}

spl_autoload_register( 'nock_autoload_classes' );


class Nock {

	/**
	 * Plugin class
	 *
	 * @var Nock
	 * @since 0.1.0
	 */
	protected static $single_instance = null;

	/**
	 * Instance of Nock_Numbers
	 *
	 * @since 0.1.0
	 * @var Nock_Numbers
	 */
	protected $numbers;

	/**
	 * Instance of Nock_Messages
	 *
	 * @since 0.1.0
	 * @var Nock_Message
	 */
	public $messages;

	/**
	 * Instance of Nock_Keyword
	 *
	 * @since 0.1.0
	 * @var Nock_Keyword
	 */
	protected $keywords;

	/**
	 * Instance of Nock_API
	 *
	 * @since 0.1.0
	 * @var Nock_API
	 */
	protected $api;

	/**
	 * Create an instance of the Operator object
	 *
	 * @since 0.1.0
	 */
	static function init() {

		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * The constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->messages    = new Nock_Message( $this );
		$this->accounts    = new Nock_Account( $this );
		$this->groups      = new Nock_Group( $this );
		$this->subscribers = new Nock_Subscriber( $this );
		$this->numbers     = new Nock_Number( $this );
		$this->keywords    = new Nock_Keyword( $this );
		$this->api         = new Nock_API( $this );
	}

	/**
	 * The hooks.
	 */
	public function hooks() {

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_js' ), 10 );

		// Admin hooks.
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Include a file from the autoloader.
	 *
	 * @since 0.1.0
	 *
	 * @param string $filename The filename to include.
	 */
	public function include_file( $filename = '' ) {

		$file_path = plugin_dir_path( __FILE__ ) . $filename . '.php';

		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		include $file_path;
	}

	public function enqueue_js() {
		wp_enqueue_script( 'nock', plugin_dir_url( __FILE__ ) . 'assets/js/nock.js' );
	}

	public function show_menu_page() {
		?>
			<div class="wrap">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<form class="form" method="post" action="options.php">
					<?php settings_fields( 'operator' ); ?>
					<?php do_settings_sections( 'operator' ); ?>
					<table class="form-table">
						<tr valign="top">
							<th scope="row">Provider Account ID</th>
							<td>
								<input type="text" name="op_provider_account_id" value="<?php echo esc_attr( get_option( 'op_provider_account_id' ) ); ?>" />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">Provider Token</th>
							<td>
								<input type="text" name="op_provider_token" value="<?php echo esc_attr( get_option( 'op_provider_token' ) ); ?>" />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">Provider Secret</th>
							<td>
								<input type="text" name="op_provider_secret" value="<?php echo esc_attr( get_option( 'op_provider_secret' ) ); ?>" />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">Phone Number</th>
							<td>
								<input type="text" name="op_phone_number" value="<?php echo esc_attr( get_option( 'op_phone_number' ) ); ?>" />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">Forwarding Number</th>
							<td>
								<input type="text" name="op_forwarding_number" value="<?php echo esc_attr( get_option( 'op_forwarding_number' ) ); ?>" />
							</td>
						</tr>
					</table>
					<?php submit_button(); ?>
				</form>
			</div>
		<?php
	}

	public function register_settings() {
		register_setting( 'operator', 'op_provider_account_id' );
		register_setting( 'operator', 'op_provider_token' );
		register_setting( 'operator', 'op_provider_secret' );
		register_setting( 'operator', 'op_phone_number' );
		register_setting( 'operator', 'op_forwarding_number' );
	}
}

add_action( 'plugins_loaded', array( Nock::init(), 'hooks' ), 10, 1 );



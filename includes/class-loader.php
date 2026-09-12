<?php
/**
 * Class loader / autoloader.
 *
 * @package AIWebsiteChatbot
 */

namespace AIWebsiteChatbot;

defined( 'ABSPATH' ) || exit;

/**
 * Lightweight PSR-4 style autoloader for plugin classes.
 */
final class Loader {

	/**
	 * Explicit class name to file path map for sub-namespace classes.
	 *
	 * @var array<string,string>
	 */
	private static array $explicit_paths = array(
		'Admin\Admin'           => 'admin/class-admin.php',
		'Frontend'              => 'public/class-public.php',
		'WooCommerce_Integration' => 'includes/class-woocommerce.php',
	);

	/**
	 * Register the autoloader.
	 *
	 * @return void
	 */
	public static function register(): void {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload a plugin class.
	 *
	 * @param string $class Fully qualified class name.
	 * @return void
	 */
	public static function autoload( string $class ): void {
		if ( 0 !== strpos( $class, 'AIWebsiteChatbot\\' ) ) {
			return;
		}

		$relative = substr( $class, strlen( 'AIWebsiteChatbot\\' ) );
		$file     = '';

		if ( isset( self::$explicit_paths[ $relative ] ) ) {
			$file = AIWC_PATH . self::$explicit_paths[ $relative ];
		} else {
			$file = AIWC_PATH . 'includes/class-' . str_replace( '_', '-', strtolower( $relative ) ) . '.php';
		}

		if ( '' !== $file && file_exists( $file ) ) {
			require_once $file;
		}
	}
}
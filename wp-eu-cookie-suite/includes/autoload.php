<?php
/**
 * PSR-4 autoloader fallback when Composer vendor/ is not present.
 *
 * @package WPEU\CookieSuite
 */

declare(strict_types=1);

spl_autoload_register(
	static function ( string $class ): void {
		$prefix = 'WPEU\\CookieSuite\\';
		if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$file     = __DIR__ . '/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

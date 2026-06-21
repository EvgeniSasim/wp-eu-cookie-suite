<?php
/**
 * Test readme.txt compliance.
 */
use PHPUnit\Framework\TestCase;

class ReadmeTest extends TestCase {

	private $readme_path;
	private $main_file_path;

	protected function setUp(): void {
		$this->readme_path    = dirname( __DIR__ ) . '/privaro-cookie-consent-banner/readme.txt';
		$this->main_file_path = dirname( __DIR__ ) . '/privaro-cookie-consent-banner/privaro-cookie-consent-banner.php';
	}

	public function test_readme_exists() {
		$this->assertFileExists( $this->readme_path );
	}

	public function test_readme_headers() {
		$content = file_get_contents( $this->readme_path );

		$this->assertStringContainsString( '=== Privaro Cookie Consent Banner ===', $content );
		$this->assertStringContainsString( 'Contributors: evgenij347', $content );
		$this->assertStringContainsString( 'Requires at least:', $content );
		$this->assertStringContainsString( 'Tested up to:', $content );
		$this->assertStringContainsString( 'Stable tag:', $content );
		$this->assertStringContainsString( 'License: GPLv2 or later', $content );
	}

	public function test_version_match() {
		$readme_content = file_get_contents( $this->readme_path );
		$php_content    = file_get_contents( $this->main_file_path );

		preg_match( '/Stable tag:\s*([\d\.]+)/i', $readme_content, $readme_matches );
		preg_match( '/Version:\s*([\d\.]+)/i', $php_content, $php_matches );

		$this->assertNotEmpty( $readme_matches, 'Stable tag not found in readme.txt' );
		$this->assertNotEmpty( $php_matches, 'Version not found in plugin header' );

		$this->assertEquals( $php_matches[1], $readme_matches[1], 'Plugin version and Stable tag do not match' );
	}

	public function test_changelog_contains_stable_tag() {
		$readme_content = file_get_contents( $this->readme_path );

		preg_match( '/Stable tag:\s*([\d\.]+)/i', $readme_content, $readme_matches );
		$this->assertNotEmpty( $readme_matches, 'Stable tag not found in readme.txt' );

		$stable_tag = $readme_matches[1];
		$this->assertMatchesRegularExpression(
			'/^= ' . preg_quote( $stable_tag, '/' ) . ' =/m',
			$readme_content,
			'readme.txt changelog must include an entry for the stable tag'
		);
	}
}

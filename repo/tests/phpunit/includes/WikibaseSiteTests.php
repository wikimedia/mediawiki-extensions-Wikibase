<?php
/**
 * Tests for the WikibaseSite class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseSite
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class WikibaseSiteTests extends MediaWikiTestCase {

	public function constructorProvider() {
		return array(
			array( 'en', 'wikipedia', 'https://en.wikipedia.org', '/wiki/$1' ),
			array( 'en', 'wikipedia', 'https://en.wikipedia.org', '/wiki/$1', 'mediawiki' ),
			array( 'en', 'wikipedia', 'https://en.wikipedia.org', '/wiki/$1', 'mediawiki', false ),
			array( 'en', 'wikipedia', 'https://en.wikipedia.org', '/wiki/$1', 'mediawiki', '/w/' ),
		);
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testConstructor() {
		$args = func_get_args();

		$reflect = new ReflectionClass( 'WikibaseSite' );
		$site = $reflect->newInstanceArgs( $args );

		$functionMap = array(
			'getId',
			'getGroup',
			'getUrl',
			'getRelativePageUrlPath',
			'getType',
			'getRelativeFilePath',
		);

		foreach ( $functionMap as $index => $functionName ) {
			if ( array_key_exists( $index, $args ) ) {
				$this->assertEquals( $args[$index], call_user_func( array( $site, $functionName ) ) );
			}
		}

		$this->assertEquals( $args[2] . $args[3], $site->getPageUrlPath() );
	}

	public function pathProvider() {
		return array(
			// url, filepath, path arg, expected
			array( 'https://en.wikipedia.org', '/w/$1', 'api.php', 'https://en.wikipedia.org/w/api.php' ),
			array( 'https://en.wikipedia.org', '/w/', 'api.php', 'https://en.wikipedia.org/w/' ),
			array( 'https://en.wikipedia.org', '/foo/page.php?name=$1', 'api.php', 'https://en.wikipedia.org/foo/page.php?name=api.php' ),
			array( 'https://en.wikipedia.org', '/w/$1', '', 'https://en.wikipedia.org/w/' ),
			array( 'https://en.wikipedia.org', '/w/$1', 'foo/bar/api.php', 'https://en.wikipedia.org/w/foo/bar/api.php' ),
		);
	}

	/**
	 * @dataProvider pathProvider
	 */
	public function testGetPath( $url, $filePath, $pathArgument, $expected ) {
		$site = new WikibaseSite( 'en', 'wikipedia', $url, '', 'unknown', $filePath );
		$this->assertEquals( $expected, $site->getPath( $pathArgument ) );
	}

	public function pageUrlProvider() {
		return array(
			// url, filepath, path arg, expected
			array( 'https://en.wikipedia.org', '/wiki/$1', 'Berlin', 'https://en.wikipedia.org/wiki/Berlin' ),
			array( 'https://en.wikipedia.org', '/wiki/', 'Berlin', 'https://en.wikipedia.org/wiki/' ),
			array( 'https://en.wikipedia.org', '/wiki/page.php?name=$1', 'Berlin', 'https://en.wikipedia.org/wiki/page.php?name=Berlin' ),
			array( 'https://en.wikipedia.org', '/wiki/$1', '', 'https://en.wikipedia.org/wiki/' ),
			array( 'https://en.wikipedia.org', '/wiki/$1', 'Berlin/sub page', 'https://en.wikipedia.org/wiki/Berlin%2Fsub%20page' ),
			array( 'https://en.wikipedia.org', '/wiki/$1', 'Cork (city)', 'https://en.wikipedia.org/wiki/Cork%20%28city%29' ),
		);
	}

	/**
	 * @dataProvider pageUrlProvider
	 */
	public function testGetPageUrl( $url, $urlPath, $pageName, $expected ) {
		$site = new WikibaseSite( 'en', 'wikipedia', $url, $urlPath, 'unknown' );
		$this->assertEquals( $expected, $site->getPageUrl( $pageName ) );
	}

}
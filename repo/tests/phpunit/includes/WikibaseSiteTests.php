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
	}

	// TODO: moar tests

}
<?php

namespace Wikibase\Client\Scribunto\Test;

if ( !class_exists( 'Scribunto_LuaEngineTestBase' ) ) {
	// This needs Scribunto
	class Scribunto_LuaWikibaseLibraryTestCase{}
	return;
}

use Language;
use Title;
use Wikibase\Client\WikibaseClient;
use Wikibase\ClientStore;
use Wikibase\Test\MockClientStore;

/**
 * Base class for Wikibase Scribunto Tests
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group WikibaseClient
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 * @author Daniel Kinzler
 */
class Scribunto_LuaWikibaseLibraryTestCase extends \Scribunto_LuaEngineTestBase {

	/* @var ClientStore */
	private $oldStore = null;

	/**
	 * Makes sure WikibaseClient uses our ClientStore mock
	 */
	private static function doMock() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$store = $wikibaseClient->getStore();

		if ( ! $store instanceof MockClientStore ) {
			$store = new MockClientStore();
			$wikibaseClient->overrideStore( $store );
		}
	}

	/**
	 * Set up stuff we need to have in place even before Scribunto does its stuff
	 *
	 * @param string $className
	 *
	 * @return \PHPUnit_Framework_TestSuite
	 */
	public static function suite( $className ) {
		self::doMock();

		static $setUp = false;
		if ( !$setUp ) {
			$testHelper = new WikibaseLuaIntegrationTestItemSetUpHelper();
			$testHelper->setUp();
			$setUp = true;
		}

		return parent::suite( $className );
	}

	protected function setUp() {
		self::doMock();

		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$this->assertInstanceOf(
			'Wikibase\Test\MockRepository',
			$wikibaseClient->getStore()->getEntityLookup(),
			'Mocking the default client EntityLookup failed'
		);

		$this->setMwGlobals( 'wgContLang', Language::factory( 'de' ) );
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();

		if ( $this->oldStore ) {
			$wikibaseClient = WikibaseClient::getDefaultInstance();
			$wikibaseClient->overrideStore( $this->oldStore );
		}
	}

	/**
	 * @return Title
	 */
	protected function getTestTitle() {
		return Title::newFromText( 'WikibaseClientLuaTest' );
	}

}

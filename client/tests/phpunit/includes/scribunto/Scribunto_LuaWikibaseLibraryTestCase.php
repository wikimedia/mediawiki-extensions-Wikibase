<?php

namespace Wikibase\Client\Scribunto\Test;

if ( !class_exists( 'Scribunto_LuaEngineTestBase' ) ) {
	// This needs Scribunto
	class Scribunto_LuaWikibaseLibraryTestCase{}
	return;
}

use Title;
use Language;
use Wikibase\Client\WikibaseClient;

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
 */
class Scribunto_LuaWikibaseLibraryTestCase extends \Scribunto_LuaEngineTestBase {

	/* @var mixed */
	private static $oldDefaultClientStore = null;

	/* @var array */
	private static $oldWgWBClientStores = null;

	/**
	 * Makes sure WikibaseClient uses our ClientStore mock
	 */
	private static function doMock() {
		global $wgWBClientStores;

		$wikibaseClient = WikibaseClient::getDefaultInstance();

		self::$oldDefaultClientStore = $wikibaseClient->getSettings()->get( 'defaultClientStore' );
		$wikibaseClient->getSettings()->setSetting( 'defaultClientStore', 'ClientStoreMock' );

		self::$oldWgWBClientStores = $wgWBClientStores;
		$wgWBClientStores = array(
			'ClientStoreMock' => '\Wikibase\Test\MockClientStore'
		);

		// Reset the store instance to make sure our Mock is really being used
		$wikibaseClient->getStore( false, 'reset' );
	}

	/**
	 * Set up stuff we need to have in place even before Scribunto does its stuff
	 *
	 * @param string $className
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
		global $wgWBClientStores;
		parent::tearDown();

		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$wikibaseClient->getSettings()->setSetting( 'defaultClientStore', self::$oldDefaultClientStore );
		$wgWBClientStores = self::$oldWgWBClientStores;
		// Reset the store instance, to make sure our Mock wont be used in other tests
		$wikibaseClient->getStore( false, 'reset' );
	}

	/**
	 * @return Title
	 */
	protected function getTestTitle() {
		return Title::newFromText( 'WikibaseClientLuaTest' );
	}
}
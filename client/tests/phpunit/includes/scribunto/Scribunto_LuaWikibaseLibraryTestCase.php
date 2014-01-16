<?php

namespace Wikibase\Client\Scribunto\Test;

if ( !class_exists( 'Scribunto_LuaEngineTestBase' ) ) {
	// This needs Scribunto
	class Scribunto_LuaWikibaseLibraryTestCase{}
	return;
}

use PHPUnit_Framework_TestSuite;
use Title;
use Language;
use Scribunto_LuaEngineTestSkip;
use Wikibase\Settings;
use Wikibase\Client\WikibaseClient;

/**
 * Base class for Wikibase Scribunto Tests
 *
 * @since 0.5
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
	/**
	 * We need to overwrite this as Scribunto registers stuff even before running setUp
	 */
	public static function suite( $className ) {
		if ( !defined( 'WB_VERSION' ) ) {
			$suite = new PHPUnit_Framework_TestSuite;
			$suite->setName( $className );
			$suite->addTest(
				new Scribunto_LuaEngineTestSkip(
					$className,
					"Skipping because WikibaseClient doesn't have a local site link table."
				)
			);
			return $suite;
		}

		return parent::suite( $className );
	}

	protected function setUp() {
		parent::setUp();

		$wikibaseClient = WikibaseClient::getDefaultInstance();

		// Get the name of the store which will be used and then update the
		// mapping to let it point to our mock.
		$storeName = Settings::get( 'defaultClientStore' ) ?
			Settings::get( 'defaultClientStore' ) : 'DirectSqlStore';

		$this->setMwGlobals(
			'wgWBClientStores',
			array( $storeName => '\Wikibase\Client\Scribunto\Test\ClientStoreMock' )
		);

		static $setUp = false;
		if ( !$setUp ) {
			$testHelper = new WikibaseLuaIntegrationTestHelper();
			$testHelper->setUp();
			$setUp = true;
		}

		// Reset the store instance to make sure our Mock is really being used
		$wikibaseClient->getStore( false, 'reset' );

		$this->assertInstanceOf(
			'Wikibase\Test\MockRepository',
			$wikibaseClient->getStore()->getEntityLookup(),
			'Mocking the default client EntityLookup failed'
		);

		$this->setMwGlobals( 'wgContLang', Language::factory( 'de' ) );
	}

	public function tearDown() {
		parent::tearDown();
		// Reset the store instance, to make sure our Mock wont be used in other tests
		WikibaseClient::getDefaultInstance()->getStore( false, 'reset' );
	}

	/**
	 * @return Title
	 */
	protected function getTestTitle() {
		return Title::newFromText( 'WikibaseClientLuaTest' );
	}
}
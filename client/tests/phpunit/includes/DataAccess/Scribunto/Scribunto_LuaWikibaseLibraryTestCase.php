<?php

namespace Wikibase\Client\Tests\DataAccess\Scribunto;

use PHPUnit_Framework_TestSuite;
use Scribunto_LuaEngineTestBase;
use Title;
use Wikibase\Client\Tests\DataAccess\WikibaseDataAccessTestItemSetUpHelper;
use Wikibase\Client\Tests\MockClientStore;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingTermLookup;
use Wikibase\WikibaseSettings;

if ( !class_exists( Scribunto_LuaEngineTestBase::class ) ) {
	/**
	 * Fake base class in case Scribunto is not available.
	 *
	 * @license GPL-2.0-or-later
	 * @author Marius Hoch < hoo@online.de >
	 */
	abstract class Scribunto_LuaWikibaseLibraryTestCase extends \PHPUnit\Framework\TestCase {

		protected function setUp() {
			$this->markTestSkipped( 'Scribunto is not available' );
		}

		public function testPlaceholder() {
			$this->fail( 'PHPunit expects this class to have tests. This should never run.' );
		}

	}

	return;
}

/**
 * Base class for Wikibase Scribunto Tests
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 * @author Daniel Kinzler
 */
abstract class Scribunto_LuaWikibaseLibraryTestCase extends Scribunto_LuaEngineTestBase {

	/**
	 * @var bool|null
	 */
	private static $oldAllowArbitraryDataAccess = null;

	/**
	 * @var bool|null
	 */
	private $oldUseKartographerMaplinkInWikitext;

	/**
	 * Whether to allow arbitrary data access or not
	 *
	 * @return bool
	 */
	protected static function allowArbitraryDataAccess() {
		return true;
	}

	/**
	 * Makes sure WikibaseClient uses our ClientStore mock
	 */
	private static function doMock() {
		$wikibaseClient = WikibaseClient::getDefaultInstance( 'reset' );

		$store = new MockClientStore( 'de' );
		$entityLookup = static::getEntityLookup();
		$store->setEntityLookup( $entityLookup );
		$wikibaseClient->overrideStore( $store );

		// Create a term lookup from the ovewritten EntityLookup or the MockClientStore one
		$wikibaseClient->overrideTermLookup(
			new EntityRetrievingTermLookup( $entityLookup ?: $store->getEntityLookup() )
		);

		$settings = $wikibaseClient->getSettings();
		if ( self::$oldAllowArbitraryDataAccess === null ) {
			// Only need to set this once, as this is supposed to be the original value
			self::$oldAllowArbitraryDataAccess = $settings->getSetting( 'allowArbitraryDataAccess' );
		}

		$settings->setSetting(
			'allowArbitraryDataAccess',
			static::allowArbitraryDataAccess()
		);

		$settings->setSetting(
			'entityAccessLimit',
			static::getEntityAccessLimit()
		);

		$testHelper = new WikibaseDataAccessTestItemSetUpHelper( $store );
		$testHelper->setUp();
	}

	private static function unMock() {
		$wikibaseClient = WikibaseClient::getDefaultInstance( 'reset' );

		if ( self::$oldAllowArbitraryDataAccess !== null ) {
			$wikibaseClient->getSettings()->setSetting(
				'allowArbitraryDataAccess',
				self::$oldAllowArbitraryDataAccess
			);
		}
	}

	/**
	 * Set up stuff we need to have in place even before Scribunto does its stuff.
	 * And remove that again after suite is done, so that other test won't get
	 * affected.
	 *
	 * @param string $className
	 *
	 * @return PHPUnit_Framework_TestSuite
	 */
	public static function suite( $className ) {
		self::doMock();

		$res = parent::suite( $className );

		self::unMock();

		return $res;
	}

	protected function setUp() {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because a local wb_terms table"
				. " is not available on a WikibaseClient only instance." );
		}
		$this->tablesUsed[] = 'wb_terms';

		parent::setUp();

		self::doMock();

		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$this->assertInstanceOf(
			MockClientStore::class,
			$wikibaseClient->getStore(),
			'Mocking the default ClientStore failed'
		);

		$this->setContentLang( 'de' );
		$this->primeTermsTable();

		// Make sure <maplink> can be used, even if Kartographer is not installed.
		$this->addMaplinkParserTag();

		$settings = $wikibaseClient->getSettings();
		$this->oldUseKartographerMaplinkInWikitext = $settings->getSetting( 'useKartographerMaplinkInWikitext' );
		$settings->setSetting( 'useKartographerMaplinkInWikitext', true );
	}

	protected function tearDown() {
		parent::tearDown();

		$settings = WikibaseClient::getDefaultInstance()->getSettings();
		$settings->setSetting( 'useKartographerMaplinkInWikitext', $this->oldUseKartographerMaplinkInWikitext );

		self::unMock();
	}

	private function primeTermsTable() {
		$db = wfGetDB( DB_MASTER );
		$db->insert(
			'wb_terms',
			[
				'term_full_entity_id' => 'P342',
				'term_entity_id' => 342,
				'term_entity_type' => 'property',
				'term_language' => 'de',
				'term_type' => 'label',
				'term_text' => 'LuaTestStringProperty',
				'term_search_key' => 'fooo'
			]
		);
	}

	private function addMaplinkParserTag() {
		$engine = $this->getEngine();
		if ( !in_array( 'maplink', $engine->getParser()->getTags() ) ) {
			$engine->getParser()->setHook(
				'maplink',
				function() {
					return 'THIS-IS-A-MAP';
				}
			);
		}
	}

	/**
	 * @return Title
	 */
	protected function getTestTitle() {
		return Title::newFromText( 'WikibaseClientDataAccessTest' );
	}

	/**
	 * @return EntityLookup|null
	 */
	protected static function getEntityLookup() {
		return null;
	}

	/**
	 * @return int
	 */
	protected static function getEntityAccessLimit() {
		return PHP_INT_MAX;
	}

}

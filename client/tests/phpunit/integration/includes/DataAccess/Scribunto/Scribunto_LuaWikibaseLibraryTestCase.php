<?php

namespace Wikibase\Client\Tests\Integration\DataAccess\Scribunto;

use ExtensionRegistry;
use Scribunto_LuaEngineTestBase;
use Title;
use Wikibase\Client\Tests\Integration\DataAccess\WikibaseDataAccessTestItemSetUpHelper;
use Wikibase\Client\Tests\Mocks\MockClientStore;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingTermLookup;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;

if ( !ExtensionRegistry::getInstance()->isLoaded( 'Scribunto' ) ) {
	/**
	 * Fake base class in case Scribunto is not available.
	 *
	 * @license GPL-2.0-or-later
	 * @author Marius Hoch < hoo@online.de >
	 */
	abstract class Scribunto_LuaWikibaseLibraryTestCase extends \PHPUnit\Framework\TestCase {

		protected function setUp(): void {
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
	private function doMock() {
		$store = new MockClientStore( 'de' );
		$entityLookup = static::getEntityLookup();
		$store->setEntityLookup( $entityLookup );
		$this->setService( 'WikibaseClient.Store', $store );

		// Create a term lookup from the overwritten EntityLookup or the MockClientStore one
		$this->setService( 'WikibaseClient.TermLookup',
			new EntityRetrievingTermLookup( $entityLookup ?: $store->getEntityLookup() ) );

		$settings = clone WikibaseClient::getSettings();
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

		$this->setService( 'WikibaseClient.Settings', $settings );

		$testHelper = new WikibaseDataAccessTestItemSetUpHelper( $store );
		$testHelper->setUp();
	}

	private function unMock() {
		if ( self::$oldAllowArbitraryDataAccess !== null ) {
			WikibaseClient::getSettings()->setSetting(
				'allowArbitraryDataAccess',
				self::$oldAllowArbitraryDataAccess
			);
		}
	}

	protected function setUp(): void {
		parent::setUp();

		$this->doMock();

		$this->setContentLang( 'de' );
		$this->overridePropertyLabelResolver();

		// Make sure <maplink> can be used, even if Kartographer is not installed.
		$this->addMaplinkParserTag();

		$settings = WikibaseClient::getSettings();
		$this->oldUseKartographerMaplinkInWikitext = $settings->getSetting( 'useKartographerMaplinkInWikitext' );
		$settings->setSetting( 'useKartographerMaplinkInWikitext', true );
	}

	protected function tearDown(): void {
		parent::tearDown();

		$settings = WikibaseClient::getSettings();
		$settings->setSetting( 'useKartographerMaplinkInWikitext', $this->oldUseKartographerMaplinkInWikitext );

		$this->unMock();
	}

	private function overridePropertyLabelResolver(): void {
		$propertyLabelResolver = $this->createMock( PropertyLabelResolver::class );
		$propertyLabelResolver->method( 'getPropertyIdsForLabels' )
			->willReturnCallback( function ( array $labels ): array {
				if ( in_array( 'LuaTestStringProperty', $labels ) ) {
					return [
						'LuaTestStringProperty' => new NumericPropertyId( 'P342' ),
					];
				}
				return [];
			} );

		$this->setService(
			'WikibaseClient.PropertyLabelResolver',
			$propertyLabelResolver
		);
	}

	private function addMaplinkParserTag(): void {
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
		return Title::makeTitle( NS_MAIN, 'WikibaseClientDataAccessTest' );
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

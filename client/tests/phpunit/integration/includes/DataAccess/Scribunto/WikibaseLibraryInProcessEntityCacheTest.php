<?php

namespace Wikibase\Client\Tests\Integration\DataAccess\Scribunto;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;

/**
 * Verifies that arbitrary data access doesn't work, if it's disabled.
 *
 * @covers \Wikibase\Client\DataAccess\Scribunto\WikibaseLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseLibraryInProcessEntityCacheTest extends WikibaseLibraryTestCase {

	/** @inheritDoc */
	protected static $moduleName = 'WikibaseLibraryInProcessEntityCacheTests';

	/**
	 * @dataProvider provideLuaData
	 * @param string $key
	 * @param string $testName
	 * @param mixed $expected
	 */
	public function testLua( $key, $testName, $expected ) {
		$entityLookup = self::getEntityLookup();

		$this->registerMockObject( $entityLookup );
		$entityLookup->expects( $this->exactly( 20 ) )
			->method( 'getEntity' )
			->willReturnCallback(
				function( ItemId $id ) {
					return new Item( $id );
				}
			);

		parent::testLua( $key, $testName, $expected );
	}

	protected function getTestModules() {
		return parent::getTestModules() + [
			'WikibaseLibraryInProcessEntityCacheTests' => __DIR__ . '/WikibaseLibraryInProcessEntityCacheTests.lua',
		];
	}

	/**
	 * @return EntityLookup
	 */
	protected static function getEntityLookup() {
		$phpunit = new self();

		static $entityLookup = null;
		if ( !$entityLookup ) {
			$entityLookup = $phpunit->createMock( EntityLookup::class );
		}

		return $entityLookup;
	}

}

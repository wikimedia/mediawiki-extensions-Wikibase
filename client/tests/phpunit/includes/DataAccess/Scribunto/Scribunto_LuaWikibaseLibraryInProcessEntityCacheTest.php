<?php

namespace Wikibase\Client\Tests\DataAccess\Scribunto;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;

/**
 * Verifies that arbitrary data access doesn't work, if it's disabled.
 *
 * @covers Wikibase\Client\DataAccess\Scribunto\Scribunto_LuaWikibaseLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class Scribunto_LuaWikibaseLibraryInProcessEntityCacheTest extends Scribunto_LuaWikibaseLibraryTestCase {

	protected static $moduleName = 'LuaWikibaseLibraryInProcessEntityCacheTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'LuaWikibaseLibraryInProcessEntityCacheTests' => __DIR__ . '/LuaWikibaseLibraryInProcessEntityCacheTests.lua',
		];
	}

	/**
	 * @return EntityLookup
	 */
	protected static function getEntityLookup() {
		$phpunit = new self();

		$entityLookup = $phpunit->getMock( EntityLookup::class );
		$entityLookup->expects( $phpunit->exactly( 20 ) )
			->method( 'getEntity' )
			->will( $phpunit->returnCallback(
				function( ItemId $id ) {
					return new Item( $id );
				}
			) );

		return $entityLookup;
	}

}

<?php

namespace Wikibase\Test;
use Wikibase\EntityCacheUpdater as EntityCacheUpdater;
use \Wikibase\EntityUpdate as EntityUpdate;
use \Wikibase\ItemObject as ItemObject;

/**
 * Tests for the Wikibase\EntityCacheUpdater class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseClient
 * @ingroup Test
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseEntityCache
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityCacheUpdaterTest extends \MediaWikiTestCase {

	public function handleChangeProvider() {
		$argLists = array();

		$sourceItem = ItemObject::newEmpty();
		$sourceItem->setId( 42 );
		$targetItem = clone $sourceItem;
		$targetItem->setLabel( 'en', 'ohi there' );
		$change = EntityUpdate::newFromEntities( $sourceItem, $targetItem );

		$argLists[] = array( $change, $sourceItem, $targetItem );

		return $argLists;
	}

	/**
	 * Data provider refuses to work for some reason o_O
	 */
	public function testHandleChange( /* EntityChange $change, ItemObject $sourceItem, ItemObject $targetItem */ ) {
		foreach ( $this->handleChangeProvider() as $argList ) {
			list( $change, $sourceItem, $targetItem ) = $argList;

			$cacheUpdater = new EntityCacheUpdater();

			$cacheUpdater->handleChange( $change );

			// TODO: test if the result matches expected behaviour
			$this->assertTrue( true );
		}
	}

	public function constructorProvider() {
		return array(
			array(),
		);
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testConstructor() {
		$reflector = new \ReflectionClass( '\Wikibase\EntityCacheUpdater' );
		$instance = $reflector->newInstanceArgs( func_get_args() );
		$this->assertInstanceOf( '\Wikibase\EntityCacheUpdater', $instance );
	}

}
	
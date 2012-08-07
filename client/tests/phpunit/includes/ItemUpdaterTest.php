<?php

namespace Wikibase\Test;
use Wikibase\ItemUpdater as ItemUpdater;
use \Wikibase\ItemChange as ItemChange;
use \Wikibase\ItemObject as ItemObject;

/**
 * Tests for the Wikibase\ItemUpdater class.
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
 * @group WikibaseItemUpdater
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemUpdaterTest extends \MediaWikiTestCase {

	public function constructorProvider() {
		return array(
			array(),
		);
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testConstructor() {
		$reflector = new \ReflectionClass( '\Wikibase\ItemUpdater' );
		$instance = $reflector->newInstanceArgs( func_get_args() );
		$this->assertInstanceOf( '\Wikibase\ItemUpdater', $instance );
	}

	public function handleChangeProvider() {
		$argLists = array();

		$sourceItem = ItemObject::newEmpty();
		$sourceItem->setId( 42 );
		$targetItem = clone $sourceItem;
		$targetItem->setLabel( 'en', 'ohi there' );
		$change = ItemChange::newFromItems( $sourceItem, $targetItem );

		$argLists[] = array( $change, $sourceItem, $targetItem );

		return $argLists;
	}

	/**
	 * @dataProvider handleChangeProvider
	 */
	public function testHandleChange( ItemChange $change, ItemObject $sourceItem, ItemObject $targetItem ) {
		$itemUpdater = new ItemUpdater();

		$itemUpdater->handleChange( $change );

		// TODO: test if the result matches expected behaviour
		$this->assertTrue( true );
	}


}
	
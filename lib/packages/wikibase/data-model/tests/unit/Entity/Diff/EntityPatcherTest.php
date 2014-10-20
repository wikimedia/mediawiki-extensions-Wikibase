<?php

namespace Wikibase\Test\Entity\Diff;

use Wikibase\DataModel\Entity\Diff\EntityDiff;
use Wikibase\DataModel\Entity\Diff\EntityPatcher;
use Wikibase\DataModel\Entity\Diff\ItemDiff;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Test\DataModel\Fixtures\EntityOfUnknownType;

/**
 * @covers Wikibase\DataModel\Entity\Diff\EntityPatcher
 *
 * @licence GNU GPL v2+
 * @author Christoph Fischer < christoph.fischer@wikimedia.de >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityPatcherTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider itemProvider
	 */
	public function testGivenEmptyDiffItemRemainsUnchanged( Item $item ) {
		$patcher = new EntityPatcher();

		$patchedEntity = $item->copy();
		$patcher->patchEntity( $patchedEntity, new ItemDiff() );

		$this->assertEquals( $item, $patchedEntity );
	}

	public function itemProvider() {
		$argLists = array();

		$nonEmptyItem = Item::newEmpty();
		$nonEmptyItem->setId( 2 );

		$argLists[] = array( Item::newEmpty() );
		$argLists[] = array( $nonEmptyItem );

		return $argLists;
	}

	public function testGivenNonSupportedEntity_exceptionIsThrown() {
		$patcher = new EntityPatcher();

		$this->setExpectedException( 'RuntimeException' );
		$patcher->patchEntity( new EntityOfUnknownType(), new EntityDiff() );
	}

}


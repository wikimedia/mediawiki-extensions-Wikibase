<?php

namespace Wikibase\DataModel\Services\Tests\Diff;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\EntityPatcher;
use Wikibase\DataModel\Services\Diff\ItemDiff;
use Wikibase\DataModel\Services\Fixtures\EntityOfUnknownType;

/**
 * @covers \Wikibase\DataModel\Services\Diff\EntityPatcher
 *
 * @license GPL-2.0-or-later
 * @author Christoph Fischer < christoph.fischer@wikimedia.de >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityPatcherTest extends TestCase {

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
		$argLists = [];

		$nonEmptyItem = new Item( new ItemId( 'Q2' ) );

		$argLists[] = [ new Item() ];
		$argLists[] = [ $nonEmptyItem ];

		return $argLists;
	}

	public function testGivenNonSupportedEntity_exceptionIsThrown() {
		$patcher = new EntityPatcher();

		$this->expectException( RuntimeException::class );
		$patcher->patchEntity( new EntityOfUnknownType(), new EntityDiff() );
	}

}

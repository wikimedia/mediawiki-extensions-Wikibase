<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Fixtures\ItemFixtures;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Services\Lookup\ItemLookupException;
use Wikibase\DataModel\Services\Lookup\LegacyAdapterItemLookup;

/**
 * @covers \Wikibase\DataModel\Services\Lookup\LegacyAdapterItemLookup
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyAdapterItemLookupTest extends TestCase {

	public function testGivenKnownItem_getItemForIdReturnsIt() {
		$item = ItemFixtures::newItem();

		$lookup = new LegacyAdapterItemLookup( new InMemoryEntityLookup( $item ) );

		$this->assertEquals(
			$item,
			$lookup->getItemForId( $item->getId() )
		);
	}

	public function testWhenItemIsNotKnown_getItemForIdReturnsNull() {
		$lookup = new LegacyAdapterItemLookup( new InMemoryEntityLookup() );

		$this->assertNull(
			$lookup->getItemForId( new ItemId( 'Q1' ) )
		);
	}

	public function testGetItemForIdThrowsCorrectExceptionType() {
		$id = new ItemId( 'Q1' );

		$legacyLookup = new InMemoryEntityLookup();
		$legacyLookup->addException( new EntityLookupException( $id ) );

		$itemLookup = new LegacyAdapterItemLookup( $legacyLookup );

		$this->expectException( ItemLookupException::class );
		$itemLookup->getItemForId( $id );
	}

}

<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\DataAccess;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\EntityLookupItemsBatchRetriever;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\EntityLookupItemsBatchRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityLookupItemsBatchRetrieverTest extends TestCase {

	public function testGetItems(): void {
		$deletedItem = new ItemId( 'Q666' );
		$item1Id = new ItemId( 'Q123' );
		$item2Id = new ItemId( 'Q321' );

		$lookup = new InMemoryEntityLookup();
		$lookup->addEntity( NewItem::withId( $item1Id )->build() );
		$lookup->addEntity( NewItem::withId( $item2Id )->build() );

		$batch = $this->newRetriever( $lookup )
			->getItems( $item1Id, $item2Id, $deletedItem );

		$this->assertEquals( $item1Id, $batch->getItem( $item1Id )->id );
		$this->assertEquals( $item2Id, $batch->getItem( $item2Id )->id );
		$this->assertNull( $batch->getItem( $deletedItem ) );
	}

	private function newRetriever( EntityLookup $entityLookup ): EntityLookupItemsBatchRetriever {
		return new EntityLookupItemsBatchRetriever( $entityLookup );
	}

}

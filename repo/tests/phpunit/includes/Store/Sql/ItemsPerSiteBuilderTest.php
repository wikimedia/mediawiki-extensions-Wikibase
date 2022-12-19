<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Entity\NullEntityPrefetcher;
use Wikibase\DataModel\Services\EntityId\EntityIdPager;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Store\Sql\SiteLinkTable;
use Wikibase\Repo\Store\Sql\ItemsPerSiteBuilder;

/**
 * @covers \Wikibase\Repo\Store\Sql\ItemsPerSiteBuilder
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class ItemsPerSiteBuilderTest extends MediaWikiIntegrationTestCase {

	private const BATCH_SIZE = 5;

	private function getTestItemId(): ItemId {
		return new ItemId( 'Q1234' );
	}

	private function getTestItem(): Item {
		return new Item( $this->getTestItemId() );
	}

	private function getSiteLinkTable(): SiteLinkTable {
		$mock = $this->createMock( SiteLinkTable::class );

		$item = $this->getTestItem();
		$mock->expects( $this->exactly( 10 ) )
			->method( 'saveLinksOfItem' )
			->willReturn( true )
			->with( $item );

		return $mock;
	}

	private function getEntityLookup(): EntityLookup {
		$mock = $this->createMock( EntityLookup::class );

		$item = $this->getTestItem();
		$mock->expects( $this->exactly( 10 ) )
			->method( 'getEntity' )
			->with( $this->getTestItemId() )
			->willReturn( $item );

		return $mock;
	}

	private function getItemsPerSiteBuilder(): ItemsPerSiteBuilder {
		$mockDomainDb = $this->createMock( RepoDomainDb::class );

		return new ItemsPerSiteBuilder(
			$this->getSiteLinkTable(),
			$this->getEntityLookup(),
			new NullEntityPrefetcher(),
			$mockDomainDb
		);
	}

	private function getEntityIdPager(): EntityIdPager {
		$mock = $this->createMock( EntityIdPager::class );

		$itemIds = [
			$this->getTestItemId(),
			$this->getTestItemId(),
			$this->getTestItemId(),
			$this->getTestItemId(),
			$this->getTestItemId(),
		];

		$mock
			->method( 'fetchIds' )
			->with( self::BATCH_SIZE )
			->willReturnOnConsecutiveCalls( $itemIds, $itemIds, [] );
		return $mock;
	}

	public function testRebuild(): void {
		$itemsPerSiteBuilder = $this->getItemsPerSiteBuilder();
		$itemsPerSiteBuilder->setBatchSize( self::BATCH_SIZE );

		$entityIdPager = $this->getEntityIdPager();
		$itemsPerSiteBuilder->rebuild( $entityIdPager );

		// The various mocks already verify they get called correctly,
		// so no need for assertions
		$this->assertTrue( true );
	}

}

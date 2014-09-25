<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\SiteLinkTable;
use Wikibase\Repo\Store\EntityIdPager;
use Wikibase\Repo\Store\SQL\ItemsPerSiteBuilder;

/**
 * @covers Wikibase\Repo\Store\SQL\ItemsPerSiteBuilder
 *
 * @license GPL 2+
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibaseRepo
 *
 * @author Marius Hoch < hoo@online.de >
 */
class ItemsPerSiteBuilderTest extends \MediaWikiTestCase {
	/**
	 * @return int
	 */
	private function getBatchSize() {
		return 5;
	}

	/**
	 * @return ItemId
	 */
	private function getTestItemId() {
		return new ItemId( 'Q1234' );
	}

	/**
	 * @return Item
	 */
	private function getTestItem() {
		static $item = null;

		if ( !$item  ) {
			$item = Item::newEmpty();
			$item->setId( $this->getTestItemId() );
		}

		return $item;
	}

	/**
	 * @return SiteLinkTable
	 */
	private function getSiteLinkTableMock() {
		$siteLinkTableMock = $this->getMockBuilder( 'Wikibase\Lib\Store\SiteLinkTable' )
			->disableOriginalConstructor()
			->getMock();

		$item = $this->getTestItem();
		$siteLinkTableMock->expects( $this->exactly( 10 ) )
			->method( 'saveLinksOfItem' )
			->will( $this->returnValue( true ) )
			->with( $this->equalTo( $item ) );

		return $siteLinkTableMock;
	}

	/**
	 * @return EntityLookup
	 */
	private function getEntityLookupMock() {
		$entityLookupMock = $this->getMockBuilder( 'Wikibase\Lib\Store\EntityLookup' )
			->disableOriginalConstructor()
			->getMock();

		$item = $this->getTestItem();
		$entityLookupMock->expects( $this->exactly( 10 ) )
			->method( 'getEntity' )
			->will( $this->returnValue( $item ) )
			->with( $this->equalTo( $this->getTestItemId() ) );

		return $entityLookupMock;
	}

	/**
	 * @return ItemsPerSiteBuilder
	 */
	private function getItemsPerSiteBuilder() {
		return new ItemsPerSiteBuilder(
			$this->getSiteLinkTableMock(),
			$this->getEntityLookupMock()
		);
	}

	/**
	 * @return EntityIdPager
	 */
	private function getEntityIdPager() {
		$entityIdPager = $this->getMock( 'Wikibase\Repo\Store\EntityIdPager' );

		$itemIds = array(
			$this->getTestItemId(),
			$this->getTestItemId(),
			$this->getTestItemId(),
			$this->getTestItemId(),
			$this->getTestItemId()
		);

		$entityIdPager->expects( $this->at( 0 ) )
			->method( 'fetchIds' )
			->will( $this->returnValue( $itemIds ) )
			->with( $this->equalTo( $this->getBatchSize() ) );

		$entityIdPager->expects( $this->at( 1 ) )
			->method( 'fetchIds' )
			->will( $this->returnValue( $itemIds ) )
			->with( $this->equalTo( $this->getBatchSize() ) );

		$entityIdPager->expects( $this->at( 2 ) )
			->method( 'fetchIds' )
			->will( $this->returnValue( array() ) )
			->with( $this->equalTo( $this->getBatchSize() ) );

		return $entityIdPager;
	}

	public function testRebuild() {
		$itemsPerSiteBuilder = $this->getItemsPerSiteBuilder();
		$itemsPerSiteBuilder->setBatchSize( $this->getBatchSize() );

		$entityIdPager = $this->getEntityIdPager();
		$itemsPerSiteBuilder->rebuild( $entityIdPager );

		// The various mocks already verify they get called correctly,
		// so no need for assertions
		$this->assertTrue( true );
	}
}

<?php

namespace Wikibase\Test;

use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\ItemContent;
use Wikibase\Repo\Maintenance\GeoDataBuilder;
use Wikibase\Repo\Store\InMemoryEntityIdPager;

/**
 * @covers Wikibase\Repo\Maintenance\GeoDataBuilder
 *
 * @since 0.5
 *
 * @group WikibaseRepo
 * @group Wikibase
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class GeoDataBuilderTest extends \MediaWikiTestCase {

	protected function setUp() {
		$this->tablesUsed[] = 'geo_tags';
		$this->tablesUsed[] = 'page_props';

		parent::setUp();
	}

	public function testRebuild() {
		$geoDataBuilder = new GeoDataBuilder(
			$this->getEntityIdPager(),
			$this->getEntityTitleLookup(),
			$this->getPropertyDataTypeLookup(),
			$this->getWikiPageEntityStore(),
			$this->getMessageReporter()
		);

		$geoDataBuilder->rebuild( new ItemId( 'Q1' ), 2, 10 );

		$this->assertTrue( true );
	}

	private function getEntityIdPager() {
		$entityIds = array(
			new ItemId( 'Q1' ),
			new PropertyId( 'P2' ),
			new ItemId( 'Q2' ),
			new ItemId( 'Q3' ),
			new ItemId( 'Q4' ),
			new ItemId( 'Q5' )
		);

		return new InMemoryEntityIdPager( $entityIds );
	}

	private function getEntityTitleLookup() {
		$entityTitleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );

		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return Title::makeTitle(
					NS_MAIN,
					$id->getEntityType() . ':' . $id->getSerialization()
				);
			} ) );

		return $entityTitleLookup;
	}

	private function getPropertyDataTypeLookup() {
		$dataTypeLookup = new InMemoryDataTypeLookup();

		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P1' ), 'url' );
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P2' ), 'commonsMedia' );
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P3' ), 'globe-coordinate' );

		return $dataTypeLookup;
	}

	private function getWikiPageEntityStore() {
		$entityStore = $this->getMockBuilder( 'Wikibase\Repo\Store\WikiPageEntityStore' )
			->disableOriginalConstructor()
			->getMock();

		$itemId = new ItemId( 'Q1' );
		$page = $this->makeWikiPage( $itemId );

		$entityStore->expects( $this->any() )
			->method( 'getWikiPageForEntity' )
			->will( $this->returnValue( $page ) );

		return $entityStore;
	}

	private function makeWikiPage( ItemId $itemId ) {
		$page = $this->getMockBuilder( 'WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$item = new Item( $itemId );
		$content = ItemContent::newFromItem( $item );

		$page->expects( $this->any() )
			->method( 'getContent' )
			->will( $this->returnValue( $content ) );

		return $page;
	}

	private function getMessageReporter() {
		$reporter = $this->getMock( 'Wikibase\Lib\Reporting\MessageReporter' );

		$reporter->expects( $this->any() )
			->method( 'reportMessage' )
			->will( $this->returnCallback( function( $msg ) {
				return "$msg\n";
			} ) );

		return $reporter;
	}

}

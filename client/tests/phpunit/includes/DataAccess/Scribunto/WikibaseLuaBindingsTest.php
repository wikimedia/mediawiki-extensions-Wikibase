<?php

namespace Wikibase\Client\Tests\DataAccess\Scribunto;

use Wikibase\Client\DataAccess\Scribunto\WikibaseLuaBindings;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Store\HashSiteLinkStore;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\SettingsArray;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers Wikibase\Client\DataAccess\Scribunto\WikibaseLuaBindings
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseScribunto
 *
 * @license GPL-2.0+
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseLuaBindingsTest extends \PHPUnit_Framework_TestCase {

	public function testConstructor() {
		$wikibaseLuaBindings = $this->getWikibaseLuaBindings(
			new MockRepository(),
			new HashSiteLinkStore()
		);

		$this->assertInstanceOf( WikibaseLuaBindings::class, $wikibaseLuaBindings );
	}

	/**
	 * @param EntityLookup $entityLookup
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param UsageAccumulator|null $usageAccumulator
	 * @return WikibaseLuaBindings
	 */
	private function getWikibaseLuaBindings(
		EntityLookup $entityLookup,
		SiteLinkLookup $siteLinkLookup,
		UsageAccumulator $usageAccumulator = null
	) {
		$labelDescriptionLookup = $this->getMock( LabelDescriptionLookup::class );
		$labelDescriptionLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue( new Term( 'xy', 'LabelString' ) ) );

		$labelDescriptionLookup->expects( $this->any() )
			->method( 'getDescription' )
			->will( $this->returnValue( new Term( 'xy', 'DescriptionString' ) ) );

		return new WikibaseLuaBindings(
			new BasicEntityIdParser(),
			$entityLookup,
			$siteLinkLookup,
			new SettingsArray(),
			$labelDescriptionLookup,
			$usageAccumulator ?: new HashUsageAccumulator(),
			"enwiki" // siteId
		);
	}

	private function hasUsage( $actualUsages, EntityId $entityId, $aspect ) {
		$usage = new EntityUsage( $entityId, $aspect );
		$key = $usage->getIdentityString();
		return isset( $actualUsages[$key] );
	}

	public function testGetEntityId() {
		$item = new Item( new ItemId( 'Q33' ) );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Rome' );

		$usages = new HashUsageAccumulator();

		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $item );

		$siteLinkStore = new HashSiteLinkStore();
		$siteLinkStore->saveLinksOfItem( $item );

		$wikibaseLuaBindings = $this->getWikibaseLuaBindings(
			$entityLookup,
			$siteLinkStore,
			$usages
		);

		$id = $wikibaseLuaBindings->getEntityId( 'Rome' );
		$this->assertSame( 'Q33', $id );

		$itemId = new ItemId( $id );
		$this->assertTrue(
			$this->hasUsage( $usages->getUsages(), $itemId, EntityUsage::TITLE_USAGE ),
			'title usage'
		);
		$this->assertFalse(
			$this->hasUsage( $usages->getUsages(), $itemId, EntityUsage::SITELINK_USAGE ),
			'sitelink usage'
		);

		$id = $wikibaseLuaBindings->getEntityId( 'Barcelona' );
		$this->assertNull( $id );
	}

	public function getLabelProvider() {
		return array(
			array( 'LabelString', 'Q123' ),
			array( null, 'DoesntExist' )
		);
	}

	/**
	 * @dataProvider getLabelProvider
	 *
	 * @param string $expected
	 * @param string $itemId
	 */
	public function testGetLabel( $expected, $itemId ) {
		$wikibaseLuaBindings = $this->getWikibaseLuaBindings(
			new MockRepository(),
			new HashSiteLinkStore()
		);

		$this->assertSame( $expected, $wikibaseLuaBindings->getLabel( $itemId ) );
	}

	public function testGetLabel_usage() {
		$usages = new HashUsageAccumulator();

		$wikibaseLuaBindings = $this->getWikibaseLuaBindings(
			new MockRepository(),
			new HashSiteLinkStore(),
			$usages
		);

		$itemId = new ItemId( 'Q7' );
		$wikibaseLuaBindings->getLabel( $itemId->getSerialization() );

		//NOTE: label usage is not tracked directly, this is done via the LabelDescriptionLookup
		$this->assertFalse( $this->hasUsage( $usages->getUsages(), $itemId, EntityUsage::TITLE_USAGE ), 'title usage' );
		$this->assertFalse( $this->hasUsage( $usages->getUsages(), $itemId, EntityUsage::ALL_USAGE ), 'all usage' );
	}

	public function getDescriptionProvider() {
		return array(
			array( 'DescriptionString', 'Q123' ),
			array( null, 'DoesntExist' )
		);
	}

	/**
	 * @dataProvider getDescriptionProvider
	 *
	 * @param string $expected
	 * @param string $itemId
	 */
	public function testGetDescription( $expected, $itemId ) {
		$wikibaseLuaBindings = $this->getWikibaseLuaBindings(
			new MockRepository(),
			new HashSiteLinkStore()
		);

		$this->assertSame( $expected, $wikibaseLuaBindings->getDescription( $itemId ) );
	}

	public function testGetDescription_usage() {
		$usages = new HashUsageAccumulator();

		$wikibaseLuaBindings = $this->getWikibaseLuaBindings(
			new MockRepository(),
			new HashSiteLinkStore(),
			$usages
		);

		$itemId = new ItemId( 'Q7' );
		$wikibaseLuaBindings->getDescription( $itemId->getSerialization() );

		$this->assertTrue( $this->hasUsage( $usages->getUsages(), $itemId, EntityUsage::OTHER_USAGE ), 'other usage' );
		$this->assertFalse( $this->hasUsage( $usages->getUsages(), $itemId, EntityUsage::LABEL_USAGE ), 'label usage' );
		$this->assertFalse( $this->hasUsage( $usages->getUsages(), $itemId, EntityUsage::TITLE_USAGE ), 'title usage' );
		$this->assertFalse( $this->hasUsage( $usages->getUsages(), $itemId, EntityUsage::ALL_USAGE ), 'all usage' );
	}

	public function getSiteLinkPageNameProvider() {
		return array(
			array( 'Beer', 'Q666' ),
			array( null, 'DoesntExist' )
		);
	}

	/**
	 * @dataProvider getSiteLinkPageNameProvider
	 *
	 * @param string $expected
	 * @param string $itemId
	 */
	public function testGetSiteLinkPageName( $expected, $itemId ) {
		$item = $this->getItem();

		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $item );

		$siteLinkStore = new HashSiteLinkStore();
		$siteLinkStore->saveLinksOfItem( $item );

		$wikibaseLuaBindings = $this->getWikibaseLuaBindings( $entityLookup, $siteLinkStore );
		$this->assertSame( $expected, $wikibaseLuaBindings->getSiteLinkPageName( $itemId ) );
	}

	public function testGetSiteLinkPageName_usage() {
		$item = $this->getItem();

		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $item );

		$siteLinkStore = new HashSiteLinkStore();
		$siteLinkStore->saveLinksOfItem( $item );

		$usages = new HashUsageAccumulator();

		$wikibaseLuaBindings = $this->getWikibaseLuaBindings(
			$entityLookup,
			$siteLinkStore,
			$usages
		);

		$itemId = $item->getId();
		$wikibaseLuaBindings->getSiteLinkPageName( $itemId->getSerialization() );

		$this->assertTrue( $this->hasUsage( $usages->getUsages(), $itemId, EntityUsage::TITLE_USAGE ), 'title usage' );
		$this->assertFalse( $this->hasUsage( $usages->getUsages(), $itemId, EntityUsage::LABEL_USAGE ), 'label usage' );
		$this->assertFalse( $this->hasUsage( $usages->getUsages(), $itemId, EntityUsage::ALL_USAGE ), 'all usage' );
	}

	protected function getItem() {
		$item = new Item( new ItemId( 'Q666' ) );
		$item->setLabel( 'en', 'Beer' );
		$item->setDescription( 'en', 'yummy beverage' );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Beer' );
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Bier' );

		return $item;
	}

}

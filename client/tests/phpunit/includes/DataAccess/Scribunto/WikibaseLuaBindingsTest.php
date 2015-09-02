<?php

namespace Wikibase\Client\Tests\DataAccess\Scribunto;

use Language;
use ParserOptions;
use Wikibase\Client\DataAccess\Scribunto\WikibaseLuaBindings;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Store\HashSiteLinkStore;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\SettingsArray;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\Client\DataAccess\Scribunto\WikibaseLuaBindings
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseScribunto
 *
 * @licence GNU GPL v2+
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

		$this->assertInstanceOf(
			'Wikibase\Client\DataAccess\Scribunto\WikibaseLuaBindings',
			$wikibaseLuaBindings
		);
	}

	/**
	 * @param EntityLookup|null $entityLookup
	 * @param UsageAccumulator|null $usageAccumulator
	 * @param ParserOptions|null $parserOptions
	 * @return WikibaseLuaBindings
	 */
	private function getWikibaseLuaBindings(
		EntityLookup $entityLookup,
		SiteLinkLookup $siteLinkLookup,
		UsageAccumulator $usageAccumulator = null,
		ParserOptions $parserOptions = null
	) {
		$labelDescriptionLookup = $this->getMock( 'Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup' );
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
			$parserOptions ?: new ParserOptions(),
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

		$itemId = $wikibaseLuaBindings->getEntityId( 'Rome' );
		$this->assertEquals( 'Q33', $itemId );

		$this->assertTrue( $this->hasUsage( $usages->getUsages(), new ItemId( $itemId ), EntityUsage::TITLE_USAGE ), 'title usage' );
		$this->assertFalse( $this->hasUsage( $usages->getUsages(), new ItemId( $itemId ), EntityUsage::SITELINK_USAGE ), 'sitelink usage' );

		$itemId = $wikibaseLuaBindings->getEntityId( 'Barcelona' );
		$this->assertSame( null, $itemId );
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

	public function testGetUserLang() {
		$parserOptions = new ParserOptions();
		$parserOptions->setUserLang( Language::factory( 'ru' ) );

		$self = $this;  // PHP 5.3 ...
		$cacheSplit = false;
		$parserOptions->registerWatcher(
			function( $optionName ) use ( $self, &$cacheSplit ) {
				$self->assertSame( 'userlang', $optionName );
				$cacheSplit = true;
			}
		);

		$wikibaseLuaBindings = $this->getWikibaseLuaBindings(
			new MockRepository(),
			new HashSiteLinkStore(),
			new HashUsageAccumulator(),
			$parserOptions
		);

		$userLang = $wikibaseLuaBindings->getUserLang();
		$this->assertSame( 'ru', $userLang );
		$this->assertTrue( $cacheSplit );
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

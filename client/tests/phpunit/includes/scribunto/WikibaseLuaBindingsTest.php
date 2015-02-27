<?php

namespace Wikibase\Client\Tests\Scribunto;

use Language;
use ParserOptions;
use Wikibase\Client\Scribunto\WikibaseLuaBindings;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\SettingsArray;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\Client\Scribunto\WikibaseLuaBindings
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
		$wikibaseLuaBindings = $this->getWikibaseLuaBindings();

		$this->assertInstanceOf(
			'Wikibase\Client\Scribunto\WikibaseLuaBindings',
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
		EntityLookup $entityLookup = null,
		UsageAccumulator $usageAccumulator = null,
		ParserOptions $parserOptions = null
	) {

		$siteLinkTable = $this->getMockBuilder( 'Wikibase\Lib\Store\SiteLinkTable' )
			->disableOriginalConstructor()
			->getMock();

		$siteLinkTable->expects( $this->any() )
			->method( 'getItemIdForLink' )
			->will( $this->returnCallback( function( $siteId, $page ) {
					return ( $page === 'Rome' ) ? new ItemId( 'Q33' ) : false;
				} )
			);

		$labelLookup = $this->getMock( 'Wikibase\Lib\Store\LabelLookup' );
		$labelLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue( new Term( 'xy', 'LabelString' ) ) );

		return new WikibaseLuaBindings(
			new BasicEntityIdParser(),
			$entityLookup ?: new MockRepository(),
			$siteLinkTable,
			new SettingsArray(),
			$labelLookup,
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
		$usages = new HashUsageAccumulator();
		$wikibaseLuaBindings = $this->getWikibaseLuaBindings( null, $usages );

		$itemId = $wikibaseLuaBindings->getEntityId( 'Rome' );
		$this->assertEquals( 'Q33' , $itemId );

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
		$wikibaseLuaBindings = $this->getWikibaseLuaBindings();

		$this->assertSame( $expected, $wikibaseLuaBindings->getLabel( $itemId ) );
	}

	public function testGetLabel_usage() {
		$usages = new HashUsageAccumulator();
		$wikibaseLuaBindings = $this->getWikibaseLuaBindings( null, $usages );

		$itemId = new ItemId( 'Q7' );
		$wikibaseLuaBindings->getLabel( $itemId->getSerialization() );

		$this->assertTrue( $this->hasUsage( $usages->getUsages(), $itemId, EntityUsage::LABEL_USAGE ), 'label usage' );
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

		$wikibaseLuaBindings = $this->getWikibaseLuaBindings( $entityLookup );
		$this->assertSame( $expected, $wikibaseLuaBindings->getSiteLinkPageName( $itemId ) );
	}

	public function testGetSiteLinkPageName_usage() {
		$item = $this->getItem();

		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $item );

		$usages = new HashUsageAccumulator();
		$wikibaseLuaBindings = $this->getWikibaseLuaBindings( $entityLookup, $usages );

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

		$wikibaseLuaBindings = $this->getWikibaseLuaBindings( null, null, $parserOptions );
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

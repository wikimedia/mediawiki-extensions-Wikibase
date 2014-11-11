<?php

namespace Wikibase\Client\Tests\Scribunto;

use Language;
use Wikibase\Client\Scribunto\WikibaseLuaBindings;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\LanguageFallbackChainFactory;
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

	private function getWikibaseLuaBindings(
		EntityLookup $entityLookup = null,
		UsageAccumulator $usageAccumulator = null
	) {
		$language = new Language( "en" );

		$siteLinkTable = $this->getMockBuilder( 'Wikibase\Lib\Store\SiteLinkTable' )
			->disableOriginalConstructor()
			->getMock();

		$siteLinkTable->expects( $this->any() )
			->method( 'getItemIdForLink' )
			->will( $this->returnCallback( function( $siteId, $page ) {
					return ( $page === 'Rome' ) ? new ItemId( 'Q33' ) : false;
				} )
			);

		$propertyDataTypeLookup = $this->getMock( 'Wikibase\DataModel\Entity\PropertyDataTypeLookup' );
		$propertyDataTypeLookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnValue( 'structured-cat' ) );

		$labelLookup = $this->getMock( 'Wikibase\Lib\Store\LabelLookup' );
		$labelLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue( 'LabelString' ) );

		return new WikibaseLuaBindings(
			new BasicEntityIdParser(),
			$entityLookup ?: new MockRepository(),
			$siteLinkTable,
			new LanguageFallbackChainFactory(),
			$language, // language
			new SettingsArray(),
			$propertyDataTypeLookup,
			$labelLookup,
			$usageAccumulator ? $usageAccumulator : new HashUsageAccumulator(),
			array( 'de', 'en', 'es', 'ja' ),
			"enwiki" // siteId
		);
	}

	private function hasUsage( $actualUsages, EntityId $entityId, $aspect ) {
		$usage = new EntityUsage( $entityId, $aspect );
		$key = $usage->getIdentityString();
		return isset( $actualUsages[$key] );
	}

	/**
	 * @dataProvider getEntityProvider
	 */
	public function testGetEntity( array $expected, Item $item, EntityLookup $entityLookup ) {
		$prefixedId = $item->getId()->getSerialization();
		$wikibaseLuaBindings = $this->getWikibaseLuaBindings( $entityLookup );

		$entityArr = $wikibaseLuaBindings->getEntity( $prefixedId );
		$actual = is_array( $entityArr ) ? array_keys( $entityArr ) : array();
		$this->assertEquals( $expected, $actual );
	}

	public function testGetEntity_usage() {
		$item = $this->getItem();
		$itemId = $item->getId();

		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $item );

		$usages = new HashUsageAccumulator();
		$wikibaseLuaBindings = $this->getWikibaseLuaBindings( $entityLookup, $usages );

		$wikibaseLuaBindings->getEntity( $itemId->getSerialization() );
		$this->assertTrue( $this->hasUsage( $usages->getUsages(), $item->getId(), EntityUsage::ALL_USAGE ), 'all usage' );
	}

	public function getEntityProvider() {
		$item = $this->getItem();

		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $item );

		$item2 = $item->newEmpty();
		$item2->setId( new ItemId( 'Q9999' ) );

		return array(
			array( array( 'id', 'type', 'descriptions', 'labels', 'sitelinks', 'schemaVersion' ), $item, $entityLookup ),
			array( array(), $item2, $entityLookup )
		);
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

	public function testGetGlobalSiteId() {
		$wikibaseLuaBindings = $this->getWikibaseLuaBindings();

		$this->assertEquals( 'enwiki', $wikibaseLuaBindings->getGlobalSiteId() );
	}

	public function getLabelProvider() {
		return array(
			array( 'LabelString', 'Q123' ),
			array( '', 'DoesntExist' )
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
			array( '', 'DoesntExist' )
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

	protected function getItem() {
		$itemId = new ItemId( 'Q666' );

		$item = Item::newEmpty();
		$item->setId( $itemId );
		$item->setLabel( 'en', 'Beer' );
		$item->setDescription( 'en', 'yummy beverage' );
		$item->addSiteLink( new SiteLink( 'enwiki', 'Beer' ) );
		$item->addSiteLink( new SiteLink( 'dewiki', 'Bier' ) );

		return $item;
	}

	/**
	 * @dataProvider provideZeroIndexedArray
	 */
	public function testZeroIndexArray ( array $array, array $expected ) {
		$this->getWikibaseLuaBindings()->renumber( $array );

		$this->assertSame( $expected, $array );
	}

	public function provideZeroIndexedArray() {
		return array(
			array(
				array( 'nyancat' => array( 0 => 'nyan', 1 => 'cat' ) ),
				array( 'nyancat' => array( 1 => 'nyan', 2 => 'cat' ) )
			),
			array(
				array( array( 'a', 'b' ) ),
				array( array( 1 => 'a', 2 => 'b' ) )
			),
			array(
				// Nested arrays
				array( array( 'a', 'b', array( 'c', 'd' ) ) ),
				array( array( 1 => 'a', 2 => 'b', 3 => array( 1 => 'c', 2 => 'd' ) ) )
			),
			array(
				// Already 1-based
				array( array( 1 => 'a', 4 => 'c', 3 => 'b' ) ),
				array( array( 1 => 'a', 4 => 'c', 3 => 'b' ) )
			),
			array(
				// Associative array
				array( array( 'foo' => 'bar', 1337 => 'Wikidata' ) ),
				array( array( 'foo' => 'bar', 1337 => 'Wikidata' ) )
			),
		);
	}

}

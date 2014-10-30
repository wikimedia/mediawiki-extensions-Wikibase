<?php

namespace Wikibase\Client\Scribunto\Test;

use Language;
use Wikibase\Client\Scribunto\WikibaseLuaBindings;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
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
		$wikibaseLibrary = $this->getWikibaseLibraryImplementation();
		$this->assertInstanceOf(
			'Wikibase\Client\Scribunto\WikibaseLuaBindings',
			$wikibaseLibrary
		);
	}

	private function getWikibaseLibraryImplementation( EntityLookup $entityLookup = null ) {
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

		return new WikibaseLuaBindings(
			new BasicEntityIdParser(),
			$entityLookup ? $entityLookup : new MockRepository(),
			$siteLinkTable,
			new LanguageFallbackChainFactory(),
			$language, // language
			new SettingsArray(),
			$propertyDataTypeLookup,
			array( 'de', 'en', 'es', 'ja' ),
			"enwiki" // siteId
		);
	}

	/**
	 * @dataProvider getEntityProvider
	 */
	public function testGetEntity( array $expected, Item $item, EntityLookup $entityLookup ) {
		$prefixedId = $item->getId()->getSerialization();
		$wikibaseLibrary = $this->getWikibaseLibraryImplementation( $entityLookup );
		$entityArr = $wikibaseLibrary->getEntity( $prefixedId );
		$actual = is_array( $entityArr ) ? array_keys( $entityArr ) : array();
		$this->assertEquals( $expected, $actual );
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
		$wikibaseLibrary = $this->getWikibaseLibraryImplementation();
		$itemId = $wikibaseLibrary->getEntityId( 'Rome' );
		$this->assertEquals( 'Q33' , $itemId );

		$itemId = $wikibaseLibrary->getEntityId( 'Barcelona' );
		$this->assertSame( null, $itemId );
	}

	public function testGetGlobalSiteId() {
		$wikibaseLibrary = $this->getWikibaseLibraryImplementation();
		$this->assertEquals( 'enwiki', $wikibaseLibrary->getGlobalSiteId() );
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
		$this->getWikibaseLibraryImplementation()->renumber( $array );
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

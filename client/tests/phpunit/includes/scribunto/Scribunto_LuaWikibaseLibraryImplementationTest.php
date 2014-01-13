<?php

namespace Wikibase\Test;

use Language;
use Scribunto_LuaWikibaseLibraryImplementation;
use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\EntityLookup;
use Wikibase\Item;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\EntityIdFormatter;

/**
 * @covers Scribunto_LuaWikibaseLibraryImplementation
 *
 * @since 0.4
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseScribunto
 * @group Scribunto_LuaWikibaseLibraryImplementationTest
 *
 * @licence GNU GPL v2+
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class Scribunto_LuaWikibaseLibraryImplementationTest extends \PHPUnit_Framework_TestCase {

	public function testConstructor() {
		$wikibaseLibrary = $this->getWikibaseLibraryImplementation();
		$this->assertInstanceOf( 'Scribunto_LuaWikibaseLibraryImplementation', $wikibaseLibrary );
	}

	private function getWikibaseLibraryImplementation( EntityLookup $entityLookup = null ) {
		$language = new Language( "en" );

		$siteLinkTable = $this->getMockBuilder( '\Wikibase\SiteLinkTable' )
			->disableOriginalConstructor()
			->getMock();

		$siteLinkTable->expects( $this->any() )
			->method( 'getItemIdForLink' )
			->will( $this->returnCallback( function( $siteId, $page ) {
					return ( $page === 'Rome' ) ? 33 : false;
				} )
			);

		return new Scribunto_LuaWikibaseLibraryImplementation(
			new BasicEntityIdParser(),
			$entityLookup ? $entityLookup : new MockRepository(),
			new EntityIdFormatter( new FormatterOptions() ),
			$siteLinkTable,
			new LanguageFallbackChainFactory(),
			$language, // language
			array( 'de', 'en', 'es', 'ja' ),
			"enwiki" // siteId
		);
	}

	/**
	 * @dataProvider getEntityProvider
	 */
	public function testGetEntity( $expected, $entity, $entityLookup ) {
		$prefixedId = $entity->getId()->getSerialization();
		$wikibaseLibrary = $this->getWikibaseLibraryImplementation( $entityLookup );
		$entityArr = $wikibaseLibrary->getEntity( $prefixedId );
		$actual = is_array( $entityArr[0] ) ? array_keys( $entityArr[0] ) : array();
		$this->assertEquals( $expected, $actual );
	}

	public function getEntityProvider() {
		$item = $this->getItem();

		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $item );

		$item2 = $item->newEmpty();
		$item2->setId( new ItemId( 'Q9999' ) );

		return array(
			array( array( 'id', 'type', 'descriptions', 'labels', 'sitelinks' ), $item, $entityLookup ),
			array( array(), $item2, $entityLookup )
		);
	}

	public function testGetEntityId() {
		$wikibaseLibrary = $this->getWikibaseLibraryImplementation();
		$itemId = $wikibaseLibrary->getEntityId( 'Rome' );
		$this->assertEquals( array( 'Q33' ), $itemId );

		$itemId = $wikibaseLibrary->getEntityId( 'Barcelona' );
		$this->assertEquals( array( null ), $itemId );
	}

	public function testGetGlobalSiteId() {
		$wikibaseLibrary = $this->getWikibaseLibraryImplementation();
		$this->assertEquals( array( 'enwiki' ), $wikibaseLibrary->getGlobalSiteId() );
	}

	public function getItem() {
		$itemId = new ItemId( 'Q666' );

		$item = Item::newEmpty();
		$item->setId( $itemId );
		$item->setLabel( 'en', 'Beer' );
		$item->setDescription( 'en', 'yummy beverage' );
		$item->addSiteLink( new SimpleSiteLink( 'enwiki', 'Beer' ) );
		$item->addSiteLink( new SimpleSiteLink( 'dewiki', 'Bier' ) );

		return $item;
	}

}

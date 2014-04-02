<?php

namespace Wikibase\Test;

use Wikibase\Client\ClientSiteLinkLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Item;
use Title;

/**
 * @covers Wikibase\Client\ClientSiteLinkLookup
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ClientSiteLinkLookupTest extends \PHPUnit_Framework_TestCase {

	static $itemData = array(
		1 => array(
			'id' => 1,
			'label' => array( 'en' => 'Foo' ),
			'links' => array(
				'dewiki' => array(
					'name' => 'Foo de',
					'badges' => array( 'Q3' )
				),
				'enwiki' => array(
					'name' => 'Foo en',
					'badges' => array( 'Q4', 'Q123' )
				),
				'srwiki' => 'Foo sr',
				'dewiktionary' => 'Foo de word',
				'enwiktionary' => 'Foo en word',
			)
		)
	);

	/**
	 * @var Item[]
	 */
	private $items;

	/**
	 * @return ClientSiteLinkLookup
	 */
	private function getClientSiteLinkLookup( $localSiteId ) {
		$mockRepo = new MockRepository();

		$this->items = array();
		foreach ( self::$itemData as $key => $data ) {
			$item = new Item( $data );
			$mockRepo->putEntity( $item );
			$this->items[$key] = $item;
		}

		return new ClientSiteLinkLookup(
			$localSiteId,
			$mockRepo,
			$mockRepo
		);
	}

	/**
	 * @dataProvider provideGetSiteLinks
	 */
	public function testGetSiteLinks( $expected, $localSiteId, Title $title, $message ) {
		$clientSiteLinkLookup = $this->getClientSiteLinkLookup( $localSiteId );

		$this->assertEquals(
			$expected,
			$clientSiteLinkLookup->getSiteLinks( $title ),
			$message
		);
	}

	public function provideGetSiteLinks() {
		$item = new Item( self::$itemData[1] );
		$sitelinks = $item->getSiteLinks();

		return array(
			array( $sitelinks, 'dewiki', Title::newFromText( 'Foo de' ), 'from dewiki title' ),
			array( $sitelinks, 'enwiktionary', Title::newFromText( 'Foo en word' ), 'from enwiktionary title' ),
			array( array(), 'enwiki', Title::newFromText( 'Bar en' ), 'from nonexisting title' ),
			array( array(), 'barwiki', Title::newFromText( 'Foo bar' ), 'from nonexisting site' ),
		);
	}

	/**
	 * @dataProvider provideGetSiteLink
	 */
	public function testGetSiteLink( $expected, $localSiteId, Title $title, $site, $message ) {
		$clientSiteLinkLookup = $this->getClientSiteLinkLookup( $localSiteId );

		$this->assertEquals(
			$expected,
			$clientSiteLinkLookup->getSiteLink( $title, $site ),
			$message
		);
	}

	public function provideGetSiteLink() {
		$item = new Item( self::$itemData[1] );

		return array(
			array( $item->getSiteLink( 'enwiki' ), 'dewiki', Title::newFromText( 'Foo de' ), 'enwiki', 'enwiki from dewiki title' ),
			array( $item->getSiteLink( 'dewiktionary' ), 'enwiktionary', Title::newFromText( 'Foo en word' ), 'dewiktionary', 'dewiktionary from enwiktionary title' ),
			array( null, 'enwiki', Title::newFromText( 'Bar en' ), 'dewiki', 'from nonexisting title' ),
			array( null, 'barwiki', Title::newFromText( 'Foo bar' ), 'enwiki', 'from nonexisting site' ),
			array( null, 'dewiki', Title::newFromText( 'Foo de' ), 'frwiki', 'nonexisting site from dewiki title' ),
		);
	}

	/**
	 * @dataProvider provideGetItem
	 */
	public function testGetItem( $expected, $localSiteId, Title $title, $message ) {
		$clientSiteLinkLookup = $this->getClientSiteLinkLookup( $localSiteId );

		$item = $clientSiteLinkLookup->getItem( $title );

		if ( $expected === null ) {
			$this->assertNull( $item, $message );
		} else {
			$this->assertEquals(
				$this->items[$expected]->toArray(),
				$item->toArray(),
				$message
			);
		}
	}

	public function provideGetItem() {
		return array(
			array( 1, 'dewiki', Title::newFromText( 'Foo de' ), 'from dewiki title' ),
			array( 1, 'enwiktionary', Title::newFromText( 'Foo en word' ), 'from enwiktionary title' ),
			array( null, 'enwiki', Title::newFromText( 'Bar en' ), 'from nonexisting title' ),
			array( null, 'barwiki', Title::newFromText( 'Foo bar' ), 'from nonexisting site' ),
		);
	}

}

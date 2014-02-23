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
class ClientSiteLinkLookupTest extends PHPUnit_Framework_TestCase {

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

	private function getClientSiteLinkLookup( $localSiteId ) {
		$mockRepo = new MockRepository();

		foreach ( self::$itemData as $data ) {
			$item = new Item( $data );
			$mockRepo->putEntity( $item );
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
		$ClientSiteLinkLookup = $this->getClientSiteLinkLookup( $localSiteId );

		$this->assertEquals(
			$expected,
			$ClientSiteLinkLookup->getSiteLinks( $title ),
			$message
		);
	}

	public function provideGetSiteLinks() {
		$sitelinks = array(
			new SiteLink( 'dewiki', 'Foo de', array( new ItemId( 'Q3' ) ) ),
			new SiteLink( 'enwiki', 'Foo en', array( new ItemId( 'Q4' ), new ItemId( 'Q123' ) ) ),
			new SiteLink( 'srwiki', 'Foo sr' ),
			new SiteLink( 'dewiktionary', 'Foo de word' ),
			new SiteLink( 'enwiktionary', 'Foo en word' )
		);

		return array(
			array( $sitelinks, 'dewiki', Title::newFromText( 'Foo de' ), 'from dewiki title' ),
			array( $sitelinks, 'enwiktionary', Title::newFromText( 'Foo en word' ), 'from enwiktionary title' ),
			array( array(), 'enwiki', Title::newFromText( 'Bar en' ), 'from nonexisting title' ),
			array( array(), 'barwiki', Title::newFromText( 'Foo bar' ), 'from nonexisting site' ),
		);
	}

}

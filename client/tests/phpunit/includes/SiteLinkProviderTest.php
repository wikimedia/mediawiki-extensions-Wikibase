<?php

namespace Wikibase\Test;

use Wikibase\Client\SiteLinkProvider;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Item;
use Title;

/**
 * @covers Wikibase\Client\SiteLinkProvider
 *
 * @since 0.4
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SiteLinkProviderTest extends \MediaWikiTestCase {

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

	private function getSiteLinkProvider( $localSiteId ) {
		$mockRepo = new MockRepository();

		foreach ( self::$itemData as $data ) {
			$item = new Item( $data );
			$mockRepo->putEntity( $item );
		}

		return new SiteLinkProvider(
			$localSiteId,
			$mockRepo,
			$mockRepo
		);
	}

	/**
	 * @dataProvider provideGetSiteLinks
	 */
	public function testGetSiteLinks( $expected, $localSiteId, Title $title, $includeBadges, $message ) {
		$siteLinkProvider = $this->getSiteLinkProvider( $localSiteId );

		$this->assertEquals(
			$expected,
			$siteLinkProvider->getSiteLinks( $title, $includeBadges ),
			$message
		);
	}

	public function provideGetSiteLinks() {
		$sitelinks = array(
			new SiteLink( 'dewiki', 'Foo de' ),
			new SiteLink( 'enwiki', 'Foo en' ),
			new SiteLink( 'srwiki', 'Foo sr' ),
			new SiteLink( 'dewiktionary', 'Foo de word' ),
			new SiteLink( 'enwiktionary', 'Foo en word' )
		);
		$sitelinksBadges = array(
			new SiteLink( 'dewiki', 'Foo de', array( new ItemId( 'Q3' ) ) ),
			new SiteLink( 'enwiki', 'Foo en', array( new ItemId( 'Q4' ), new ItemId( 'Q123' ) ) ),
			new SiteLink( 'srwiki', 'Foo sr' ),
			new SiteLink( 'dewiktionary', 'Foo de word' ),
			new SiteLink( 'enwiktionary', 'Foo en word' )
		);

		return array(
			// this actually does not work because the MockRepository is too smart at this point
			//array( $sitelinks, 'dewiki', Title::newFromText( 'Foo de' ), false, 'from dewiki title' ),
			//array( $sitelinks, 'enwiktionary', Title::newFromText( 'Foo en word' ), false, 'from enwiktionary title' ),
			array( $sitelinksBadges, 'dewiki', Title::newFromText( 'Foo de' ), true, 'from dewiki title, including badges' ),
			array( $sitelinksBadges, 'srwiki', Title::newFromText( 'Foo sr' ), true, 'from srwiki title, including badges' ),
			array( array(), 'dewiki', Title::newFromText( 'Bar de' ), false, 'nonexisting title' ),
			array( array(), 'enwiki', Title::newFromText( 'Bar en' ), true, 'nonexisting title, including badges' ),
			array( array(), 'barwiki', Title::newFromText( 'Foo bar' ), false, 'nonexisting site' ),
		);
	}

}

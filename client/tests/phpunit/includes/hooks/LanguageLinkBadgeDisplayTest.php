<?php

namespace Wikibase\Test;

use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\ClientSiteLinkLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Entity\Item;
use Title;

/**
 * @covers Wikibase\Client\Hooks\LanguageLinkBadgeDisplay
 *
 * @since 0.5
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class LanguageLinkBadgeDisplayTest extends \MediaWikiTestCase {

	static $itemData = array(
		1 => array(
			'id' => 1,
			'links' => array(
				'dewiki' => 'Georg Friedrich Haendel',
				'enwiki' => array(
					'name' => 'George Frideric Handel',
					'badges' => array( 'Q2', 'Q3' )
				),
				'nlwiki' => 'Georg Friedrich Haendel'
			)
		),
		2 => array(
			'id' => 2,
			'links' => array(
				'dewiki' => 'Benutzer:Testbenutzer',
				'enwiki' => array(
					'name' => 'User:Testuser',
					'badges' => array( 'Q1' )
				)
			)
		),
		3 => array(
			'id' => 3,
			'label' => array(
				'en' => 'Good article',
				'de' => 'Lesenswerter Artikel'
			)
		)
	);

	private function getLanguageLinkBadgeDisplay() {
		$mockRepo = new MockRepository();

		foreach ( self::$itemData as $data ) {
			$item = new Item( $data );
			$mockRepo->putEntity( $item );
		}

		$sites = MockSiteStore::newFromTestSites()->getSites();
		$clientSiteLinkLookup = new ClientSiteLinkLookup( 'dewiki', $mockRepo, $mockRepo );

		return new LanguageLinkBadgeDisplay(
			$clientSiteLinkLookup,
			$mockRepo,
			$sites,
			array( 'Q3' ),
			'de'
		);
	}

	/**
	 * @dataProvider assignBadgesProvider
	 */
	public function testAssignBadges( $expected, Title $title, Title $languageLinkTitle, $message ) {
		$languageLinkBadgeDisplay = $this->getLanguageLinkBadgeDisplay();

		$languageLink = array();
		$languageLinkBadgeDisplay->assignBadges( $title, $languageLinkTitle, $languageLink );

		$this->assertEquals( $expected, $languageLink, $message );
	}

	public function assignBadgesProvider() {
		$languageLink1 = array(
			'class' => 'badge-Q2 badge-Q3',
			'itemtitle' => 'Lesenswerter Artikel'
		);
		$languageLink2 = array(
			'class' => 'badge-Q1'
		);
		return array(
			array( $languageLink1, Title::newFromText( 'Georg Friedrich Haendel' ), Title::makeTitle( NS_MAIN, 'George Frideric Handel', '', 'en' ), 'passing enwiki title' ),
			array( $languageLink2, Title::newFromText( 'Benutzer:Testbenutzer' ), Title::makeTitle( NS_USER, 'Testuser', '', 'en' ), 'passing enwiki non-main namespace title' ),
			array( array(), Title::newFromText( 'Georg Friedrich Haendel' ), Title::makeTitle( NS_MAIN, 'Georg Friedrich Haendel', '', 'nl' ), 'passing nlwiki title' ),
			array( array(), Title::newFromText( 'Johann Sebastian Bach' ), Title::makeTitle( NS_MAIN, 'Johann Sebastian Bach', '', 'en' ), 'passing an unknown title' ),
			array( array(), Title::newFromText( 'Georg Friedrich Haendel' ), Title::makeTitle( NS_MAIN, 'Georg Friedrich Haendel', '', 'it' ), 'passing a site without link' ),
		);
	}

}

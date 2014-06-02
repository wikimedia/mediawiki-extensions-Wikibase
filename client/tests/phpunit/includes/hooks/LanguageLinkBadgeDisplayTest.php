<?php

namespace Wikibase\Test;

use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\ClientSiteLinkLookup;
use Wikibase\DataModel\Entity\Item;
use Language;
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
					'badges' => array( 'Q3', 'Q2' )
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
					'badges' => array( 'Q3', 'Q4' )
				)
			)
		),
		3 => array(
			'id' => 3,
			'label' => array(
				'en' => 'Good article',
				'de' => 'Lesenswerter Artikel'
			)
		),
		4 => array(
			'id' => 4,
			'label' => array(
				'en' => 'Featured article',
				'de' => 'Exzellenter Artikel'
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
		$badgeClassNames = array( 'Q4' => 'foo', 'Q3' => 'bar' );

		return new LanguageLinkBadgeDisplay(
			$clientSiteLinkLookup,
			$mockRepo,
			$sites,
			$badgeClassNames,
			Language::factory( 'de' )
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
			'class' => 'badge-Q3 badge-Q2 bar',
			'itemtitle' => 'Lesenswerter Artikel'
		);
		$languageLink2 = array(
			'class' => 'badge-Q3 badge-Q4 bar foo',
			'itemtitle' => 'Lesenswerter Artikel, Exzellenter Artikel'
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

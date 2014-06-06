<?php

namespace Wikibase\Test;

use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\ClientSiteLinkLookup;
use Wikibase\DataModel\Entity\Item;
use Language;
use Title;
use Wikibase\DataModel\Entity\ItemId;

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

	private function getItems() {
		$items = array();

		$item = Item::newEmpty();
		$item->setId( 1 );
		$item->getSiteLinkList()
			->addNewSiteLink( 'dewiki', 'Georg Friedrich Haendel' )
			->addNewSiteLink( 'nlwiki', 'Georg Friedrich Haendel' )
			->addNewSiteLink( 'enwiki', 'George Frideric Handel', array( new ItemId( 'Q3' ), new ItemId( 'Q2' ) ) );
		$items[] = $item;

		$item = Item::newEmpty();
		$item->setId( 2 );
		$item->getSiteLinkList()
			->addNewSiteLink( 'dewiki', 'Benutzer:Testbenutzer' )
			->addNewSiteLink( 'enwiki', 'User:Testuser', array( new ItemId( 'Q3' ), new ItemId( 'Q4' ) ) );
		$items[] = $item;

		$item = Item::newEmpty();
		$item->setId( 3 );
		$item->setLabel( 'en', 'Good article' );
		$item->setLabel( 'de', 'Lesenswerter Artikel' );
		$items[] = $item;

		$item = Item::newEmpty();
		$item->setId( 4 );
		$item->setLabel( 'en', 'Featured article' );
		$item->setLabel( 'de', 'Exzellenter Artikel' );
		$items[] = $item;

		return $items;
	}

	private function getLanguageLinkBadgeDisplay() {
		$mockRepo = new MockRepository();

		foreach ( $this->getItems() as $item ) {
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

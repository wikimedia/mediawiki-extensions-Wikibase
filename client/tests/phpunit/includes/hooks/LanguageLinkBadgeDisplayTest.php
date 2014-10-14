<?php

namespace Wikibase\Test;

use FauxRequest;
use Language;
use OutputPage;
use ParserOutput;
use RequestContext;
use Title;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\TermLookupService;

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
		$links = $item->getSiteLinkList();
		$links->addNewSiteLink( 'dewiki', 'Georg Friedrich Haendel' );
		$links->addNewSiteLink( 'nlwiki', 'Georg Friedrich Haendel' );
		$links->addNewSiteLink( 'enwiki', 'George Frideric Handel', array( new ItemId( 'Q3' ), new ItemId( 'Q2' ) ) );
		$items[] = $item;

		$item = Item::newEmpty();
		$item->setId( 2 );
		$links = $item->getSiteLinkList();
		$links->addNewSiteLink( 'dewiki', 'Benutzer:Testbenutzer' );
		$links->addNewSiteLink( 'enwiki', 'User:Testuser', array( new ItemId( 'Q3' ), new ItemId( 'Q4' ) ) );
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

		$badgeClassNames = array( 'Q4' => 'foo', 'Q3' => 'bar' );

		return new LanguageLinkBadgeDisplay(
			new TermLookupService( $mockRepo ),
			$badgeClassNames,
			Language::factory( 'de' )
		);
	}

	/**
	 * @dataProvider attachBadgesToOutputProvider
	 */
	public function testAttachBadgesToOutput( $expected, $languageLinks ) {
		$languageLinkBadgeDisplay = $this->getLanguageLinkBadgeDisplay();
		$parserOutput = new ParserOutput();

		$languageLinkBadgeDisplay->attachBadgesToOutput( $languageLinks, $parserOutput );

		$this->assertEquals( $expected, $parserOutput->getExtensionData( 'wikibase_badges' ) );
	}

	public function attachBadgesToOutputProvider() {
		$q2 = new ItemId( 'Q2' );
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );

		$link0 = new SiteLink( 'jawiki', 'Bah' );
		$link1 = new SiteLink( 'dewiki', 'Foo', array( $q3, $q2 ) );
		$link2 = new SiteLink( 'enwiki', 'Bar', array( $q3, $q4 ) );

		$badge1 = array(
			'class' => 'badge-Q3 bar badge-Q2',
			'label' => 'Lesenswerter Artikel'
		);
		$badge2 = array(
			'class' => 'badge-Q3 bar badge-Q4 foo',
			'label' => 'Lesenswerter Artikel, Exzellenter Artikel'
		);

		return array(
			'empty' => array( array(), array() ),
			'no badges' => array( array(), array( $link0 ) ),
			'some badges' => array(
				array( 'dewiki' => $badge1, 'enwiki' => $badge2 ),
				array( 'jawiki' => $link0, 'dewiki' => $link1, 'enwiki' => $link2 )
			),
		);
	}

	public function testApplyBadges() {
		$badges = array(
			'en' => array(
				'class' => 'badge-Q3',
				'label' => 'Lesenswerter Artikel',
			)
		);

		$link = array(
			'href' => 'http://acme.com',
			'class' => 'foo',
		);

		$expected = array(
			'href' => 'http://acme.com',
			'class' => 'foo badge-Q3',
			'itemtitle' => 'Lesenswerter Artikel',
		);

		$languageLinkTitle = Title::makeTitle( NS_MAIN, 'Test', '', 'en' );

		$context = new RequestContext( new FauxRequest() );
		$output = new OutputPage( $context );
		$output->setProperty( 'wikibase_badges', $badges );

		$languageLinkBadgeDisplay = $this->getLanguageLinkBadgeDisplay();
		$languageLinkBadgeDisplay->applyBadges( $link, $languageLinkTitle, $output );

		$this->assertEquals( $expected, $link );
	}

}

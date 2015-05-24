<?php

namespace Wikibase\Client\Tests\Hooks;

use Language;
use Wikibase\Client\Hooks\SidebarLinkBadgeDisplay;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\Client\Hooks\SidebarLinkBadgeDisplay
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Thomas Pellissier Tanon
 */
class SidebarLinkBadgeDisplayTest extends \MediaWikiTestCase {

	private function getItems() {
		$items = array();

		$item = new Item( new ItemId( 'Q3' ) );
		$item->setLabel( 'en', 'Good article' );
		$item->setLabel( 'de', 'Lesenswerter Artikel' );
		$items[] = $item;

		$item = new Item( new ItemId( 'Q4' ) );
		$item->setLabel( 'en', 'Featured article' );
		$item->setLabel( 'de', 'Exzellenter Artikel' );
		$items[] = $item;

		return $items;
	}

	/**
	 * @return SidebarLinkBadgeDisplay
	 */
	private function getSidebarLinkBadgeDisplay() {
		$entityLookup = new MockRepository();

		foreach ( $this->getItems() as $item ) {
			$entityLookup->putEntity( $item );
		}

		$badgeClassNames = array( 'Q4' => 'foo', 'Q3' => 'bar' );

		return new SidebarLinkBadgeDisplay(
			$entityLookup,
			$badgeClassNames,
			Language::factory( 'de' )
		);
	}

	/**
	 * @dataProvider getBadgeInfoProvider
	 */
	public function testGetBadgeInfo( $expected, $badges ) {
		$sidebarLinkBadgeDisplay = $this->getSidebarLinkBadgeDisplay();

		$this->assertEquals( $expected, $sidebarLinkBadgeDisplay->getBadgeInfo( $badges ) );
	}

	public function getBadgeInfoProvider() {
		$q2 = new ItemId( 'Q2' );
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );

		return array(
			array( array(), array() ),
			array(
				array(
					'class' => 'badge-Q3 bar badge-Q2',
					'label' => 'Lesenswerter Artikel'
				),
				array( $q3, $q2 )
			),
			array(
				array(
					'class' => 'badge-Q3 bar badge-Q4 foo',
					'label' => 'Lesenswerter Artikel, Exzellenter Artikel'
				),
				array( $q3, $q4 )
			),
		);
	}

	public function testApplyBadges() {
		$badgeInfo = array(
			'class' => 'badge-Q3',
			'label' => 'Lesenswerter Artikel',
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

		$sidebarLinkBadgeDisplay = $this->getSidebarLinkBadgeDisplay();
		$sidebarLinkBadgeDisplay->applyBadgeToLink( $link, $badgeInfo );

		$this->assertEquals( $expected, $link );
	}

}

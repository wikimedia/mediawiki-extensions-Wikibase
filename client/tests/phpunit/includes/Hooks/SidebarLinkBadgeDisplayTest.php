<?php

namespace Wikibase\Client\Tests\Hooks;

use Language;
use Wikibase\Client\Hooks\SidebarLinkBadgeDisplay;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;

/**
 * @covers Wikibase\Client\Hooks\SidebarLinkBadgeDisplay
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @license GNU GPL v2+
 */
class SidebarLinkBadgeDisplayTest extends \MediaWikiTestCase {

	/**
	 * @return LabelDescriptionLookup
	 */
	private function getLabelDescriptionLookup() {
		$labelDescriptionLookup = $this->prophesize( LabelDescriptionLookup::class );

		$labelDescriptionLookup->getLabel( new ItemId( 'Q3' ) )->willReturn(
			new Term( 'de', 'Lesenswerter Artikel' )
		);
		$labelDescriptionLookup->getLabel( new ItemId( 'Q4' ) )->willReturn(
			new Term( 'de', 'Exzellenter Artikel' )
		);

		return $labelDescriptionLookup->reveal();
	}

	/**
	 * @return SidebarLinkBadgeDisplay
	 */
	private function getSidebarLinkBadgeDisplay() {

		$badgeClassNames = [ 'Q4' => 'foo', 'Q3' => 'bar' ];

		return new SidebarLinkBadgeDisplay(
			$this->getLabelDescriptionLookup(),
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

		return [
			[ [ 'class' => '', 'label' => '' ], [] ],
			[
				[
					'class' => 'badge-Q3 bar badge-Q2',
					'label' => 'Lesenswerter Artikel'
				],
				[ $q3, $q2 ]
			],
			[
				[
					'class' => 'badge-Q3 bar badge-Q4 foo',
					'label' => 'Lesenswerter Artikel, Exzellenter Artikel'
				],
				[ $q3, $q4 ]
			],
		];
	}

	public function testApplyBadges() {
		$badgeInfo = [
			'class' => 'badge-Q3',
			'label' => 'Lesenswerter Artikel',
		];

		$link = [
			'href' => 'http://acme.com',
			'class' => 'foo',
		];

		$expected = [
			'href' => 'http://acme.com',
			'class' => 'foo badge-Q3',
			'itemtitle' => 'Lesenswerter Artikel',
		];

		$sidebarLinkBadgeDisplay = $this->getSidebarLinkBadgeDisplay();
		$sidebarLinkBadgeDisplay->applyBadgeToLink( $link, $badgeInfo );

		$this->assertEquals( $expected, $link );
	}

}

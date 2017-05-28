<?php

namespace Wikibase\Client\Tests\Hooks;

use Language;
use Wikibase\Client\Hooks\SidebarLinkBadgeDisplay;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;

/**
 * @covers Wikibase\Client\Hooks\SidebarLinkBadgeDisplay
 *
 * @since 0.5
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

	/**
	 * @return LabelDescriptionLookup
	 */
	private function getLabelDescriptionLookup() {
		$labelLookup = $this->getMock( LabelDescriptionLookup::class );

		$labelLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnCallback(
				function ( EntityId $entityId ) {
					$serialization = $entityId->getSerialization();
					if ( $serialization === 'Q3' ) {
						return new Term( 'de', 'Lesenswerter Artikel' );
					} elseif ( $serialization === 'Q4' ) {
						return new Term( 'de', 'Exzellenter Artikel' );
					} else {
						return null;
					}
				}
			) );

		return $labelLookup;
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
			[ [ 'class' => '',  'label' => '' ], [] ],
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

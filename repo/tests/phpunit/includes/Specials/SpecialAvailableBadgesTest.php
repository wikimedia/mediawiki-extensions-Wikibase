<?php

namespace Wikibase\Repo\Tests\Specials;

use Language;
use SpecialPageTestBase;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\PrefetchingTermLookup;
use Wikibase\Repo\Specials\SpecialAvailableBadges;

/**
 * @covers Wikibase\Repo\Specials\SpecialAvailableBadges
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gamil.com>
 */
class SpecialAvailableBadgesTest extends SpecialPageTestBase {

	protected function setUp() {
		parent::setUp();

		$this->setMwGlobals( [ 'wgContLang' => Language::factory( 'qqx' ) ] );
	}

	protected function newSpecialPage() {
		$prefetchingTermLookup = $this->getMockBuilder( PrefetchingTermLookup::class )->getMock();
		$prefetchingTermLookup->expects( $this->any() )
			->method( 'getDescription' )
			->willReturn( 'Whatever' );

		$entityTitleLookup = $this->getMockBuilder( EntityTitleLookup::class )->getMock();
		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->willReturnCallback( function ( ItemId $itemId ) {
				return Title::makeTitle( 0, $itemId->getSerialization() );
			} );

		$badgeItems = [ 'Q4' => 'unkown-type' ];
		return new SpecialAvailableBadges(
			$prefetchingTermLookup,
			$entityTitleLookup,
			$badgeItems
		);
	}

	public function testExecute() {
		list( $output, ) = $this->executeSpecialPage( '' );

		$this->assertInternalType( 'string', $output );
		$this->assertContains( 'mw-specialpage-summary', $output );
		$this->assertContains( 'wikibase-availablebadges-summary', $output );

		$this->assertContains( '<li><span class="wb-badge unkown-type"></span>', $output );
		$this->assertContains( 'Q4', $output );
		$this->assertContains( 'Whatever', $output );
	}

}

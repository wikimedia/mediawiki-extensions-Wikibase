<?php

namespace Wikibase\Repo\Tests\Specials;

use SpecialPageTestBase;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\Repo\Specials\SpecialAvailableBadges;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Specials\SpecialAvailableBadges
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gamil.com>
 */
class SpecialAvailableBadgesTest extends SpecialPageTestBase {

	protected function setUp() : void {
		parent::setUp();

		$this->setContentLang( 'qqx' );
	}

	protected function newSpecialPage() {
		$prefetchingTermLookup = $this->getMockBuilder( PrefetchingTermLookup::class )->getMock();
		$prefetchingTermLookup->expects( $this->any() )
			->method( 'getDescription' )
			->willReturn( 'Test badge item' );

		$entityTitleLookup = $this->getMockBuilder( EntityTitleLookup::class )->getMock();
		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->willReturnCallback( function ( ItemId $itemId ) {
				return Title::makeTitle( 0, $itemId->getSerialization() );
			} );

		$badgeItems = [ 'Q4' => 'test-badge' ];
		return new SpecialAvailableBadges(
			$prefetchingTermLookup,
			$entityTitleLookup,
			WikibaseRepo::getDefaultInstance()->getLanguageFallbackChainFactory(),
			$badgeItems
		);
	}

	public function testExecute() {
		list( $output, ) = $this->executeSpecialPage( '' );

		$this->assertIsString( $output );
		$this->assertStringContainsString( 'mw-specialpage-summary', $output );
		$this->assertStringContainsString( 'wikibase-availablebadges-summary', $output );

		$this->assertStringContainsString( '<li><span class="wb-badge test-badge"></span>', $output );
		$this->assertStringContainsString( 'Q4', $output );
		$this->assertStringContainsString( 'Test badge item', $output );
	}

}

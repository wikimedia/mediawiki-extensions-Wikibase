<?php

namespace Wikibase\Repo\Tests\Specials;

use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkRendererFactory;
use SpecialPageTestBase;
use Title;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityTitleLookup;
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

	protected function setUp(): void {
		parent::setUp();

		$this->setContentLang( 'qqx' );
		$this->setUserLang( 'qqx' );
	}

	protected function newSpecialPage() {
		$prefetchingTermLookup = $this->createMock( PrefetchingTermLookup::class );
		$prefetchingTermLookup->method( 'getDescription' )
			->willReturn( 'Test badge item' );

		$entityTitleLookup = $this->createMock( EntityTitleLookup::class );
		$entityTitleLookup->method( 'getTitleForId' )
			->willReturnCallback( function ( ItemId $itemId ) {
				return Title::makeTitle( 0, $itemId->getSerialization() );
			} );

		$badgeItems = [ 'Q4' => 'test-badge' ];
		return new SpecialAvailableBadges(
			$entityTitleLookup,
			WikibaseRepo::getLanguageFallbackChainFactory(),
			$prefetchingTermLookup,
			new SettingsArray( [ 'badgeItems' => $badgeItems ] )
		);
	}

	public function testExecute() {
		$linkRenderer = $this->createMock( LinkRenderer::class );
		$linkRenderer->method( 'makeLink' )->willReturnCallback( static function ( $target, $text ) {
			return $text ?? Title::castFromLinkTarget( $target )->getPrefixedText();
		} );
		$lrFactory = $this->createMock( LinkRendererFactory::class );
		$lrFactory->method( 'create' )->willReturn( $linkRenderer );
		$this->setService( 'LinkRendererFactory', $lrFactory );
		list( $output, ) = $this->executeSpecialPage( '' );

		$this->assertIsString( $output );
		$this->assertStringContainsString( 'mw-specialpage-summary', $output );
		$this->assertStringContainsString( 'wikibase-availablebadges-summary', $output );

		$this->assertStringContainsString( '<li><span class="wb-badge test-badge"></span>', $output );
		$this->assertStringContainsString( 'Q4', $output );
		$this->assertStringContainsString( 'Test badge item', $output );
	}

}

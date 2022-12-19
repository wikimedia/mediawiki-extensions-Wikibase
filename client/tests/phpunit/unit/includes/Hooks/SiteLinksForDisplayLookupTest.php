<?php

namespace Wikibase\Client\Tests\Integration\Hooks;

use MediaWiki\HookContainer\HookContainer;
use Psr\Log\LoggerInterface;
use Title;
use Wikibase\Client\Hooks\SiteLinksForDisplayLookup;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @covers \Wikibase\Client\Hooks\SiteLinksForDisplayLookup
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class SiteLinksForDisplayLookupTest extends \MediaWikiUnitTestCase {

	public function testGetSiteLinksForPageTitle_unknownTitle() {
		$title = Title::makeTitle( NS_MAIN, 'Foo' );

		$siteLinkLookup = $this->createMock( SiteLinkLookup::class );
		$siteLinkLookup
			->expects( $this->once() )
			->method( 'getItemIdForLink' )
			->with( 'srwiki', 'Foo' )
			->willReturn( null );

		$entityLookup = $this->createMock( EntityLookup::class );
		$entityLookup
			->expects( $this->never() )
			->method( 'getEntity' );

		$siteLinksForDisplayLookup = new SiteLinksForDisplayLookup(
			$siteLinkLookup,
			$entityLookup,
			$this->createMock( UsageAccumulator::class ),
			$this->createMock( HookContainer::class ),
			$this->createMock( LoggerInterface::class ),
			'srwiki'
		);

		$this->assertArrayEquals( [], $siteLinksForDisplayLookup->getSiteLinksForPageTitle( $title ) );
	}

	public function testGetSiteLinksForPageTitle_unknownEntity() {
		$title = Title::makeTitle( NS_MAIN, 'Foo' );

		$siteLinkLookup = $this->createMock( SiteLinkLookup::class );
		$siteLinkLookup
			->expects( $this->once() )
			->method( 'getItemIdForLink' )
			->with( 'srwiki', 'Foo' )
			->willReturn( new ItemId( 'Q1' ) );

		$entityLookup = $this->createMock( EntityLookup::class );
		$entityLookup
			->expects( $this->once() )
			->method( 'getEntity' )
			->with( new ItemId( 'Q1' ) )
			->willReturn( null );

		$siteLinksForDisplayLookup = new SiteLinksForDisplayLookup(
			$siteLinkLookup,
			$entityLookup,
			$this->createMock( UsageAccumulator::class ),
			$this->createMock( HookContainer::class ),
			$this->createMock( LoggerInterface::class ),
			'srwiki'
		);

		$this->assertArrayEquals( [], $siteLinksForDisplayLookup->getSiteLinksForPageTitle( $title ) );
	}

	public function testGetSiteLinksForPageTitle_knownEntity() {
		$title = $this->createMock( Title::class );
		$title->method( 'getPrefixedText' )->willReturn( 'Foo sr' );
		$item = new Item( new ItemId( 'Q1' ) );
		$item->setLabel( 'en', 'Foo' );
		$links = $item->getSiteLinkList();
		$links->addNewSiteLink( 'dewiki', 'Foo de' );
		$links->addNewSiteLink( 'enwiki', 'Foo en', [ new ItemId( 'Q17' ) ] );
		$links->addNewSiteLink( 'srwiki', 'Foo sr' );
		$links->addNewSiteLink( 'dewiktionary', 'Foo de word' );
		$links->addNewSiteLink( 'enwiktionary', 'Foo en word' );

		$siteLinkLookup = $this->createMock( SiteLinkLookup::class );
		$siteLinkLookup
			->expects( $this->once() )
			->method( 'getItemIdForLink' )
			->with( 'srwiki', 'Foo sr' )
			->willReturn( new ItemId( 'Q1' ) );

		$entityLookup = $this->createMock( EntityLookup::class );
		$entityLookup
			->expects( $this->once() )
			->method( 'getEntity' )
			->with( new ItemId( 'Q1' ) )
			->willReturn( $item );

		$usageAccumulator = $this->createMock( UsageAccumulator::class );

		$hookContainer = $this->createMock( HookContainer::class );
		$hookContainer->expects( $this->once() )
			->method( 'run' )
			->with( 'WikibaseClientSiteLinksForItem', [
				$item,
				$links->toArray(),
				$usageAccumulator,
			] )
			->willReturnCallback( function ( string $hook, array $args ) {
				$links = &$args[1];
				$links['frwikisource'] = new SiteLink( 'frwikisource', 'FooSource' );
				$links['enwiki'] = new SiteLink( 'enwiki', 'Foo en', [ new ItemId( 'Q42' ) ] );
				return true;
			} );

		$siteLinksForDisplayLookup = new SiteLinksForDisplayLookup(
			$siteLinkLookup,
			$entityLookup,
			$this->createMock( UsageAccumulator::class ),
			$hookContainer,
			$this->createMock( LoggerInterface::class ),
			'srwiki'
		);

		$expectedLinks = $links->toArray();
		$expectedLinks['frwikisource'] = new SiteLink( 'frwikisource', 'FooSource' );
		$expectedLinks['enwiki'] = new SiteLink( 'enwiki', 'Foo en', [ new ItemId( 'Q42' ) ] );
		$this->assertArrayEquals( $expectedLinks, $siteLinksForDisplayLookup->getSiteLinksForPageTitle( $title ), false, true );
	}
}

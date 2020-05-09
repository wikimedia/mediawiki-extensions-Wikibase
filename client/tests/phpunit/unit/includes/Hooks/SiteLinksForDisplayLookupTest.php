<?php

namespace Wikibase\Client\Tests\Integration\Hooks;

use Psr\Log\LoggerInterface;
use Title;
use Wikibase\Client\Hooks\SiteLinksForDisplayLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
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
		$title = Title::newFromText( 'Foo' );

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
			$this->createMock( LoggerInterface::class ),
			'srwiki'
		);

		$this->assertArrayEquals( [], $siteLinksForDisplayLookup->getSiteLinksForPageTitle( $title ) );
	}

	public function testGetSiteLinksForPageTitle_unknownEntity() {
		$title = Title::newFromText( 'Foo' );

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
			$this->createMock( LoggerInterface::class ),
			'srwiki'
		);

		$this->assertArrayEquals( [], $siteLinksForDisplayLookup->getSiteLinksForPageTitle( $title ) );
	}

	public function testGetSiteLinksForPageTitle_knownEntity() {
		$title = Title::newFromText( 'Foo sr' );
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

		$siteLinksForDisplayLookup = new SiteLinksForDisplayLookup(
			$siteLinkLookup,
			$entityLookup,
			$this->createMock( LoggerInterface::class ),
			'srwiki'
		);

		$this->assertArrayEquals( $links->toArray(), $siteLinksForDisplayLookup->getSiteLinksForPageTitle( $title ), false, true );
	}
}

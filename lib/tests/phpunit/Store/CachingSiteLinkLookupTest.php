<?php

namespace Wikibase\Lib\Tests\Store;

use HashBagOStuff;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\CachingSiteLinkLookup;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @covers Wikibase\Lib\Store\CachingSiteLinkLookup
 *
 * @group WikibaseStore
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class CachingSiteLinkLookupTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function testGetItemIdForLink_cacheHit() {
		$cache = new HashBagOStuff();
		$cache->set( 'wikibase:sitelinks-by-page:foowiki:bar', 'Q42' );

		$cachingSiteLinkLookup = new CachingSiteLinkLookup(
			$this->getMock( SiteLinkLookup::class ),
			$cache
		);

		$this->assertSame(
			'Q42',
			$cachingSiteLinkLookup->getItemIdForLink( 'foowiki', 'bar' )->getSerialization()
		);
	}

	public function testGetItemIdForLink_cacheMiss() {
		$cache = new HashBagOStuff();
		$lookup = $this->getMock( SiteLinkLookup::class );
		$lookup->expects( $this->once() )
			->method( 'getItemIdForLink' )
			->with( 'foowiki', 'bar' )
			->will( $this->returnValue( new ItemId( 'Q42' ) ) );

		$cachingSiteLinkLookup = new CachingSiteLinkLookup(
			$lookup,
			$cache
		);

		$this->assertSame(
			'Q42',
			$cachingSiteLinkLookup->getItemIdForLink( 'foowiki', 'bar' )->getSerialization()
		);

		// Make sure the new value also made it into the cache
		$this->assertSame(
			'Q42',
			$cache->get( 'wikibase:sitelinks-by-page:foowiki:bar' )
		);
	}

	public function testGetItemIdForSiteLink_cacheHit() {
		$siteLink = new SiteLink( 'foowiki', 'bar' );
		$cache = new HashBagOStuff();
		$cache->set( 'wikibase:sitelinks-by-page:foowiki:bar', 'Q42' );

		$cachingSiteLinkLookup = new CachingSiteLinkLookup(
			$this->getMock( SiteLinkLookup::class ),
			$cache
		);

		$this->assertSame(
			'Q42',
			$cachingSiteLinkLookup->getItemIdForSiteLink( $siteLink )->getSerialization()
		);
	}

	public function testGetItemIdForSiteLink_cacheMiss() {
		$siteLink = new SiteLink( 'foowiki', 'bar' );
		$cache = new HashBagOStuff();
		$lookup = $this->getMock( SiteLinkLookup::class );
		$lookup->expects( $this->once() )
			->method( 'getItemIdForLink' )
			->with( 'foowiki', 'bar' )
			->will( $this->returnValue( new ItemId( 'Q42' ) ) );

		$cachingSiteLinkLookup = new CachingSiteLinkLookup(
			$lookup,
			$cache
		);

		$this->assertSame(
			'Q42',
			$cachingSiteLinkLookup->getItemIdForSiteLink( $siteLink )->getSerialization()
		);

		// Make sure the new value also made it into the cache
		$this->assertSame(
			'Q42',
			$cache->get( 'wikibase:sitelinks-by-page:foowiki:bar' )
		);
	}

	public function testGetSiteLinksForItem_cacheHit() {
		$siteLinks = [ new SiteLink( 'foowiki', 'bar' ) ];

		$cache = new HashBagOStuff();
		$cache->set( 'wikibase:sitelinks:Q42', $siteLinks );

		$cachingSiteLinkLookup = new CachingSiteLinkLookup(
			$this->getMock( SiteLinkLookup::class ),
			$cache
		);

		$this->assertSame(
			$siteLinks,
			$cachingSiteLinkLookup->getSiteLinksForItem( new ItemId( 'Q42' ) )
		);
	}

	public function testGetSiteLinksForItem_cacheMiss() {
		$siteLinks = [ new SiteLink( 'foowiki', 'bar' ) ];
		$q42 = new ItemId( 'Q42' );

		$cache = new HashBagOStuff();
		$lookup = $this->getMock( SiteLinkLookup::class );
		$lookup->expects( $this->once() )
			->method( 'getSiteLinksForItem' )
			->with( $q42 )
			->will( $this->returnValue( $siteLinks ) );

		$cachingSiteLinkLookup = new CachingSiteLinkLookup( $lookup, $cache );

		$this->assertSame(
			$siteLinks,
			$cachingSiteLinkLookup->getSiteLinksForItem( new ItemId( 'Q42' ) )
		);

		// Make sure the new value also made it into the cache
		$this->assertSame( $siteLinks, $cache->get( 'wikibase:sitelinks:Q42' ) );
	}

	public function testGetLinks() {
		// getLinks is a simple pass through
		$lookup = $this->getMock( SiteLinkLookup::class );
		$lookup->expects( $this->once() )
			->method( 'getLinks' )
			->with( [ 1 ], [ 'a' ], [ 'b' ] )
			->will( $this->returnValue( 'bar' ) );

		$cachingSiteLinkLookup = new CachingSiteLinkLookup( $lookup, new HashBagOStuff() );

		$this->assertSame(
			'bar',
			$cachingSiteLinkLookup->getLinks( [ 1 ], [ 'a' ], [ 'b' ] )
		);
	}

}

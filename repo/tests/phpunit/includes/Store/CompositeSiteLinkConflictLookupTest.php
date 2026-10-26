<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Store;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Repo\Store\CompositeSiteLinkConflictLookup;
use Wikibase\Repo\Store\SiteLinkConflictLookup;

/**
 * @covers \Wikibase\Repo\Store\CompositeSiteLinkConflictLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class CompositeSiteLinkConflictLookupTest extends TestCase {

	public function testGetConflictsForItem_noConflicts(): void {
		$itemId = new ItemId( 'Q1' );
		$sitelinkList = new SiteLinkList( [] );
		$forWrite = false;
		$lookup1 = $this->createMock( SiteLinkConflictLookup::class );
		$lookup1->expects( $this->once() )
			->method( 'getConflictsForItem' )
			->with( $itemId, $sitelinkList, $forWrite )
			->willReturn( [] );
		$lookup2 = $this->createMock( SiteLinkConflictLookup::class );
		$lookup2->expects( $this->once() )
			->method( 'getConflictsForItem' )
			->with( $itemId, $sitelinkList, $forWrite )
			->willReturn( [] );
		$compositeLookup = new CompositeSiteLinkConflictLookup( [ $lookup1, $lookup2 ] );

		$conflicts = $compositeLookup->getConflictsForItem( $itemId, $sitelinkList, $forWrite );

		$this->assertSame( [], $conflicts );
	}

	public function testGetConflictsForItem_conflicts(): void {
		$itemId = new ItemId( 'Q1' );
		$sitelinkList = new SiteLinkList( [] );
		$forWrite = true;
		$expectedConflicts = [ [
			'siteId' => 'site',
			'sitePage' => 'Page',
			'itemId' => null,
		] ];
		$lookup1 = $this->createMock( SiteLinkConflictLookup::class );
		$lookup1->expects( $this->once() )
			->method( 'getConflictsForItem' )
			->with( $itemId, $sitelinkList, $forWrite )
			->willReturn( [] );
		$lookup2 = $this->createMock( SiteLinkConflictLookup::class );
		$lookup2->expects( $this->once() )
			->method( 'getConflictsForItem' )
			->with( $itemId, $sitelinkList, $forWrite )
			->willReturn( $expectedConflicts );
		$lookup3 = $this->createMock( SiteLinkConflictLookup::class );
		$lookup3->expects( $this->never() )
			->method( 'getConflictsForItem' );
		$compositeLookup = new CompositeSiteLinkConflictLookup( [ $lookup1, $lookup2, $lookup3 ] );

		$actualConflicts = $compositeLookup->getConflictsForItem( $itemId, $sitelinkList, $forWrite );

		$this->assertSame( $expectedConflicts, $actualConflicts );
	}

}

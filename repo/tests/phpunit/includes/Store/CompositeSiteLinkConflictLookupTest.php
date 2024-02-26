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
		$db = DB_REPLICA;
		$lookup1 = $this->createMock( SiteLinkConflictLookup::class );
		$lookup1->expects( $this->once() )
			->method( 'getConflictsForItem' )
			->with( $itemId, $sitelinkList, $db )
			->willReturn( [] );
		$lookup2 = $this->createMock( SiteLinkConflictLookup::class );
		$lookup2->expects( $this->once() )
			->method( 'getConflictsForItem' )
			->with( $itemId, $sitelinkList, $db )
			->willReturn( [] );
		$compositeLookup = new CompositeSiteLinkConflictLookup( [ $lookup1, $lookup2 ] );

		$conflicts = $compositeLookup->getConflictsForItem( $itemId, $sitelinkList, $db );

		$this->assertSame( [], $conflicts );
	}

	public function testGetConflictsForItem_conflicts(): void {
		$itemId = new ItemId( 'Q1' );
		$sitelinkList = new SiteLinkList( [] );
		$db = DB_PRIMARY;
		$expectedConflicts = [ [
			'siteId' => 'site',
			'sitePage' => 'Page',
			'itemId' => null,
		] ];
		$lookup1 = $this->createMock( SiteLinkConflictLookup::class );
		$lookup1->expects( $this->once() )
			->method( 'getConflictsForItem' )
			->with( $itemId, $sitelinkList, $db )
			->willReturn( [] );
		$lookup2 = $this->createMock( SiteLinkConflictLookup::class );
		$lookup2->expects( $this->once() )
			->method( 'getConflictsForItem' )
			->with( $itemId, $sitelinkList, $db )
			->willReturn( $expectedConflicts );
		$lookup3 = $this->createMock( SiteLinkConflictLookup::class );
		$lookup3->expects( $this->never() )
			->method( 'getConflictsForItem' );
		$compositeLookup = new CompositeSiteLinkConflictLookup( [ $lookup1, $lookup2, $lookup3 ] );

		$actualConflicts = $compositeLookup->getConflictsForItem( $itemId, $sitelinkList, $db );

		$this->assertSame( $expectedConflicts, $actualConflicts );
	}

}

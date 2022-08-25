<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Store;

use HashBagOStuff;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\Store\BagOStuffSiteLinkConflictLookup;

/**
 * @covers \Wikibase\Repo\Store\BagOStuffSiteLinkConflictLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class BagOStuffSiteLinkConflictLookupTest extends TestCase {

	public function testGetConflictsForItem_primary_writesAllSiteLinks(): void {
		$item = NewItem::withId( 'Q1' )
			->andSiteLink( 'site1', 'One' )
			->andSiteLink( 'site2', 'Two' )
			->build();
		$bagOStuff = new HashBagOStuff();
		$conflictLookup = new BagOStuffSiteLinkConflictLookup( $bagOStuff );

		$conflicts = $conflictLookup->getConflictsForItem( $item, DB_PRIMARY );

		$this->assertSame( [], $conflicts );
		$this->assertTrue( $bagOStuff->hasKey( $this->cacheKey( 'site1', 'One' ) ) );
		$this->assertTrue( $bagOStuff->hasKey( $this->cacheKey( 'site2', 'Two' ) ) );
	}

	public function testGetConflictsForItem_primary_clearsConflicts(): void {
		$item = NewItem::withId( 'Q1' )
			->andSiteLink( 'site1', 'One' )
			->andSiteLink( 'site2', 'Two' )
			->andSiteLink( 'site3', 'Three' )
			->andSiteLink( 'site4', 'Four' )
			->build();
		$bagOStuff = new HashBagOStuff();
		$bagOStuff->set( $this->cacheKey( 'site2', 'Two' ), 'Q2' );
		$bagOStuff->set( $this->cacheKey( 'site4', 'Four' ), 'Q2' );
		$conflictLookup = new BagOStuffSiteLinkConflictLookup( $bagOStuff );

		$conflicts = $conflictLookup->getConflictsForItem( $item, DB_PRIMARY );

		$this->assertCount( 2, $conflicts );
		$this->assertContainsEquals( [
			'siteId' => 'site2',
			'sitePage' => 'Two',
			'itemId' => new ItemId( 'Q2' ),
		], $conflicts );
		$this->assertContainsEquals( [
			'siteId' => 'site4',
			'sitePage' => 'Four',
			'itemId' => new ItemId( 'Q2' ),
		], $conflicts );
		$this->assertContainsOnlyInstancesOf( ItemId::class, array_column( $conflicts, 'itemId' ) );
		$this->assertFalse( $bagOStuff->hasKey( $this->cacheKey( 'site1', 'One' ) ) );
		$this->assertTrue( $bagOStuff->hasKey( $this->cacheKey( 'site2', 'Two' ) ) );
		$this->assertFalse( $bagOStuff->hasKey( $this->cacheKey( 'site3', 'Three' ) ) );
		$this->assertTrue( $bagOStuff->hasKey( $this->cacheKey( 'site4', 'Four' ) ) );
	}

	public function testGetConflictsForItem_primary_ignoresSelfConflict(): void {
		$item = NewItem::withId( 'Q1' )
			->andSiteLink( 'site1', 'One' )
			->andSiteLink( 'site2', 'Two' )
			->build();
		$bagOStuff = new HashBagOStuff();
		$bagOStuff->set( $this->cacheKey( 'site1', 'One' ), 'Q1' );
		$conflictLookup = new BagOStuffSiteLinkConflictLookup( $bagOStuff );

		$conflicts = $conflictLookup->getConflictsForItem( $item, DB_PRIMARY );

		$this->assertSame( [], $conflicts );
		$this->assertTrue( $bagOStuff->hasKey( $this->cacheKey( 'site1', 'One' ) ) );
		$this->assertTrue( $bagOStuff->hasKey( $this->cacheKey( 'site2', 'Two' ) ) );
	}

	public function testGetConflictsForItem_replica_noConflicts(): void {
		$item = NewItem::withId( 'Q1' )
			->andSiteLink( 'site1', 'One' )
			->andSiteLink( 'site2', 'Two' )
			->build();
		$bagOStuff = new HashBagOStuff();
		$conflictLookup = new BagOStuffSiteLinkConflictLookup( $bagOStuff );

		$conflicts = $conflictLookup->getConflictsForItem( $item, DB_REPLICA );

		$this->assertSame( [], $conflicts );
		$this->assertFalse( $bagOStuff->hasKey( $this->cacheKey( 'site1', 'One' ) ) );
		$this->assertFalse( $bagOStuff->hasKey( $this->cacheKey( 'site2', 'Two' ) ) );
	}

	public function testGetConflictsForItem_replica_conflicts(): void {
		$item = NewItem::withId( 'Q1' )
			->andSiteLink( 'site1', 'One' )
			->andSiteLink( 'site2', 'Two' )
			->build();
		$bagOStuff = new HashBagOStuff();
		$bagOStuff->set( $this->cacheKey( 'site1', 'One' ), 'Q2' );
		$conflictLookup = new BagOStuffSiteLinkConflictLookup( $bagOStuff );

		$conflicts = $conflictLookup->getConflictsForItem( $item, DB_REPLICA );

		$this->assertEquals( [ [
			'siteId' => 'site1',
			'sitePage' => 'One',
			'itemId' => new ItemId( 'Q2' ),
		] ], $conflicts );
		$this->assertInstanceOf( ItemId::class, $conflicts[0]['itemId'] );
		$this->assertSame( 'Q2', $bagOStuff->get( $this->cacheKey( 'site1', 'One' ) ) );
		$this->assertFalse( $bagOStuff->hasKey( $this->cacheKey( 'site2', 'Two' ) ) );
	}

	public function testClearConflictsForItem(): void {
		$item = NewItem::withId( 'Q1' )
			->andSiteLink( 'site1', 'One' )
			->andSiteLink( 'site2', 'Two' )
			->build();
		$bagOStuff = new HashBagOStuff();
		$bagOStuff->set( $this->cacheKey( 'site1', 'One' ), 'Q1' );
		$bagOStuff->set( $this->cacheKey( 'site3', 'Three' ), 'Q2' );
		$conflictLookup = new BagOStuffSiteLinkConflictLookup( $bagOStuff );

		$conflictLookup->clearConflictsForItem( $item );

		$this->assertFalse( $bagOStuff->hasKey( $this->cacheKey( 'site1', 'One' ) ) );
		$this->assertFalse( $bagOStuff->hasKey( $this->cacheKey( 'site2', 'Two' ) ) );
		$this->assertTrue( $bagOStuff->hasKey( $this->cacheKey( 'site3', 'Three' ) ) );
	}

	private function cacheKey( string $siteId, string $pageName ): string {
		return ( new HashBagOStuff() )->makeKey(
			'wikibase-BagOStuffSiteLinkConflictLookup',
			$siteId,
			$pageName
		);
	}

}

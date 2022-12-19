<?php

namespace Wikibase\Lib\Tests\Store;

use Psr\Log\NullLogger;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;

/**
 * @covers \Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor
 *
 * @group WikibaseStore
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class PrefetchingWikiPageEntityMetaDataAccessorTest extends \PHPUnit\Framework\TestCase {

	public function testPrefetch() {
		$fromReplica = LookupConstants::LATEST_FROM_REPLICA;
		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );
		$q3 = new ItemId( 'Q3' );

		$lookup = $this->createMock( WikiPageEntityMetaDataAccessor::class );
		$lookup->expects( $this->once() )
			->method( 'loadRevisionInformation' )
			->with(
				[
					$q1->getSerialization() => $q1,
					$q3->getSerialization() => $q3,
					$q2->getSerialization() => $q2,
				],
				$fromReplica
			)
			->willReturn( [
				'Q1' => 'Nyan',
				'Q2' => 'cat',
				'Q3' => '~=[,,_,,]:3',
			] );

		$accessor = new PrefetchingWikiPageEntityMetaDataAccessor( $lookup, new NullLogger() );

		// Prefetch Q1 and Q3
		$accessor->prefetch( [ $q1, $q3 ] );
		// Prefetch Q1 once more to test de-duplication
		$accessor->prefetch( [ $q1 ] );

		// This will trigger all three to be loaded
		$rows = $accessor->loadRevisionInformation( [ $q2 ], $fromReplica );
		$result = $rows[$q2->getSerialization()];

		$this->assertSame( 'cat', $result );

		// No need to load this, already in cache
		$rows = $accessor->loadRevisionInformation( [ $q3 ], $fromReplica );
		$result = $rows[$q3->getSerialization()];

		$this->assertSame( '~=[,,_,,]:3', $result );
	}

	/**
	 * Test asking for more than $maxCacheKeys at once, verifying that prefetch
	 * automatically resizes the cache to handle that.
	 */
	public function testPrefetch_moreAtOnce() {
		$fromReplica = LookupConstants::LATEST_FROM_REPLICA;
		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );
		$q3 = new ItemId( 'Q3' );
		$expected = [
			'Q1' => 'Nyan',
			'Q2' => 'cat',
			'Q3' => '~=[,,_,,]:3',
		];

		$lookup = $this->createMock( WikiPageEntityMetaDataAccessor::class );
		$lookup->expects( $this->once() )
			->method( 'loadRevisionInformation' )
			->with( [
				$q1->getSerialization() => $q1,
				$q3->getSerialization() => $q3,
				$q2->getSerialization() => $q2 ] )
			->willReturn( $expected );

		$accessor = new PrefetchingWikiPageEntityMetaDataAccessor( $lookup, new NullLogger(), 2 );

		// This will trigger all three to be loaded
		$result = $accessor->loadRevisionInformation( [ $q1, $q2, $q3 ], $fromReplica );

		$this->assertSame( $expected, $result );
	}

	/**
	 * Test asking for more prefetches than $maxCacheKeys so that prefetch needs to
	 * discard some entities in order to store the ones that are immediately needed.
	 */
	public function testPrefetch_discardPrefetch() {
		$fromReplica = LookupConstants::LATEST_FROM_REPLICA;
		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );
		$q3 = new ItemId( 'Q3' );
		$expected = [
			'Q1' => 'Nyan',
			'Q2' => 'cat',
		];

		$lookup = $this->createMock( WikiPageEntityMetaDataAccessor::class );
		$lookup->expects( $this->once() )
			->method( 'loadRevisionInformation' )
			->with( [
				$q1->getSerialization() => $q1,
				$q2->getSerialization() => $q2 ] )
			->willReturn( $expected );

		$accessor = new PrefetchingWikiPageEntityMetaDataAccessor( $lookup, new NullLogger(), 2 );

		// Ask to prefetch $q1 and $q3
		$accessor->prefetch( [ $q1, $q3 ] );

		// Load $q1 and $q2... should not load $q3 as we don't have space to cache that data.
		$result = $accessor->loadRevisionInformation( [ $q1, $q2 ], $fromReplica );

		$this->assertSame( $expected, $result );
	}

	public function testLoadRevisionInformation() {
		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q5 = new ItemId( 'Q5' );

		$fromMaster = LookupConstants::LATEST_FROM_MASTER;
		$fromReplica = LookupConstants::LATEST_FROM_REPLICA;

		$lookup = $this->createMock( WikiPageEntityMetaDataAccessor::class );
		$lookup->expects( $this->exactly( 3 ) )
			->method( 'loadRevisionInformation' )
			->willReturnCallback( function( array $entityIds, $mode ) {
				$ret = [];

				/**
				 * @var EntityId $entityId
				 */
				foreach ( $entityIds as $entityId ) {
					$ret[$entityId->getSerialization()] = $mode . ':' . $entityId->getSerialization();
				}

				return $ret;
			} );

		$accessor = new PrefetchingWikiPageEntityMetaDataAccessor( $lookup, new NullLogger() );
		// Prefetch Q1 and Q3
		$accessor->prefetch( [ $q1, $q3 ] );

		// This will trigger loading Q1, Q2 and Q3
		$result = $accessor->loadRevisionInformation( [ $q2 ], $fromReplica );

		$this->assertSame( [ 'Q2' => "$fromReplica:Q2" ], $result );

		// This can be served entirely from cache
		$result = $accessor->loadRevisionInformation( [ $q1, $q3 ], $fromReplica );

		$this->assertSame(
			[ 'Q1' => "$fromReplica:Q1", 'Q3' => "$fromReplica:Q3" ],
			$result
		);

		// Fetch Q2 and Q5. Q2 is already cached Q5 needs to be loaded
		$result = $accessor->loadRevisionInformation( [ $q2, $q5 ], $fromReplica );

		$this->assertSame(
			[ 'Q2' => "$fromReplica:Q2", 'Q5' => "$fromReplica:Q5" ],
			$result
		);

		// Fetch Q4 from master
		$result = $accessor->loadRevisionInformation( [ $q4 ], $fromMaster );

		$this->assertSame( [ 'Q4' => "$fromMaster:Q4" ], $result );

		// Fetch Q2 and Q4, both from cache
		$result = $accessor->loadRevisionInformation( [ $q2, $q4 ], $fromReplica );

		$this->assertSame(
			[ 'Q2' => "$fromReplica:Q2", 'Q4' => "$fromMaster:Q4" ],
			$result
		);
	}

	/**
	 * Make sure we do the actual fetch with the right $mode set.
	 */
	public function testLoadRevisionInformation_mode() {
		$q1 = new ItemId( 'Q1' );

		$lookup = $this->createMock( WikiPageEntityMetaDataAccessor::class );
		$lookup->expects( $this->once() )
			->method( 'loadRevisionInformation' )
			->with(
				[ $q1->getSerialization() => $q1 ],
				'load-mode'
			)
			->willReturn( [ 'Q1' => 'data' ] );

		$accessor = new PrefetchingWikiPageEntityMetaDataAccessor( $lookup, new NullLogger() );

		// This loads Q1 with $mode = 'load-mode'
		$result = $accessor->loadRevisionInformation( [ $q1 ], 'load-mode' );

		$this->assertSame( [ 'Q1' => 'data' ], $result );
	}

	public function testLoadRevisionInformationByRevisionId() {
		// This function is a very simple, it's just a wrapper around the
		// lookup function.
		$q1 = new ItemId( 'Q1' );

		$lookup = $this->createMock( WikiPageEntityMetaDataAccessor::class );
		$lookup->expects( $this->once() )
			->method( 'loadRevisionInformationByRevisionId' )
			->with( $q1, 123, LookupConstants::LATEST_FROM_MASTER )
			->willReturn( 'passthrough' );

		$accessor = new PrefetchingWikiPageEntityMetaDataAccessor( $lookup, new NullLogger() );

		$result = $accessor->loadRevisionInformationByRevisionId( $q1, 123 );

		$this->assertSame( 'passthrough', $result );
	}

	public function testLoadLatestRevisionIds_usesCache() {
		$mode = LookupConstants::LATEST_FROM_REPLICA;
		$q1 = new ItemId( 'Q1' );
		$lookup = $this->createMock( WikiPageEntityMetaDataAccessor::class );
		$lookup->expects( $this->once() )
			->method( 'loadRevisionInformation' )
			->with( [ $q1->getSerialization() => $q1 ], $mode )
			->willReturn( [ $q1->getSerialization() => (object)[
				'page_latest' => 100,
				'page_is_redirect' => false,
			] ] );
		$lookup->expects( $this->never() )
			->method( 'loadLatestRevisionIds' );
		$accessor = new PrefetchingWikiPageEntityMetaDataAccessor( $lookup, new NullLogger() );
		$accessor->loadRevisionInformation( [ $q1 ], $mode );

		$latestRevisionIds = $accessor->loadLatestRevisionIds( [ $q1 ], $mode );

		$this->assertSame( [ $q1->getSerialization() => 100 ], $latestRevisionIds );
	}

	public function testLoadLatestRevisionIds_usesLookup() {
		$mode = LookupConstants::LATEST_FROM_REPLICA;
		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );
		$lookup = $this->createMock( WikiPageEntityMetaDataAccessor::class );
		$lookup->expects( $this->once() )
			->method( 'loadRevisionInformation' )
			->with( [ $q1->getSerialization() => $q1 ], $mode )
			->willReturn( [ $q1->getSerialization() => (object)[
				'page_latest' => 100,
				'page_is_redirect' => false,
			] ] );
		$lookup->expects( $this->once() )
			->method( 'loadLatestRevisionIds' )
			->with( [ $q2 ], $mode )
			->willReturn( [ $q2->getSerialization() => 200 ] );
		$accessor = new PrefetchingWikiPageEntityMetaDataAccessor( $lookup, new NullLogger() );
		$accessor->loadRevisionInformation( [ $q1 ], $mode );

		$latestRevisionIds = $accessor->loadLatestRevisionIds( [ $q2 ], $mode );

		$this->assertSame( [ $q2->getSerialization() => 200 ], $latestRevisionIds );
	}

	public function testLoadLatestRevisionIds_mergesCacheAndLookup() {
		$mode = LookupConstants::LATEST_FROM_REPLICA;
		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$lookup = $this->createMock( WikiPageEntityMetaDataAccessor::class );
		$lookup->expects( $this->once() )
			->method( 'loadRevisionInformation' )
			->with( [ $q1->getSerialization() => $q1, $q3->getSerialization() => $q3 ], $mode )
			->willReturn( [
				$q1->getSerialization() => (object)[
					'page_latest' => 100,
					'page_is_redirect' => false,
				],
				$q3->getSerialization() => (object)[
					'page_latest' => 300,
					'page_is_redirect' => false,
				],
			] );
		$lookup->expects( $this->once() )
			->method( 'loadLatestRevisionIds' )
			->with( [ $q2, $q4 ], $mode )
			->willReturn( [
				$q2->getSerialization() => 200,
				$q4->getSerialization() => 400,
			] );
		$accessor = new PrefetchingWikiPageEntityMetaDataAccessor( $lookup, new NullLogger() );
		$accessor->loadRevisionInformation( [ $q1, $q3 ], $mode );

		$latestRevisionIds = $accessor->loadLatestRevisionIds( [ $q1, $q2, $q3, $q4 ], $mode );

		$expected = [
			$q1->getSerialization() => 100,
			$q2->getSerialization() => 200,
			$q3->getSerialization() => 300,
			$q4->getSerialization() => 400,
		];
		ksort( $latestRevisionIds );
		ksort( $expected );
		$this->assertSame(
			$expected,
			$latestRevisionIds
		);
	}

	public function testLoadLatestRevisionIds_masterSkipsCache() {
		$mode = LookupConstants::LATEST_FROM_MASTER;
		$q1 = new ItemId( 'Q1' );
		$lookup = $this->createMock( WikiPageEntityMetaDataAccessor::class );
		$lookup->expects( $this->once() )
			->method( 'loadRevisionInformation' )
			->with( [ $q1 ], $mode )
			->willReturn( [ $q1->getSerialization() => (object)[
				'page_latest' => 100,
				'page_is_redirect' => false,
			] ] );
		$lookup->expects( $this->once() )
			->method( 'loadLatestRevisionIds' )
			->with( [ $q1 ], $mode )
			->willReturn( [ $q1->getSerialization() => 101 ] );
		$accessor = new PrefetchingWikiPageEntityMetaDataAccessor( $lookup, new NullLogger() );
		$accessor->loadRevisionInformation( [ $q1 ], $mode );

		$latestRevisionIds = $accessor->loadLatestRevisionIds( [ $q1 ], $mode );

		$this->assertSame( [ $q1->getSerialization() => 101 ], $latestRevisionIds );
	}

	public function testLoadLatestRevisionIds_doesPrefetch() {
		$mode = LookupConstants::LATEST_FROM_REPLICA;
		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );

		$lookup = $this->createMock( WikiPageEntityMetaDataAccessor::class );
		$lookup->expects( $this->once() )
			->method( 'loadRevisionInformation' )
			->with( [ $q1->getSerialization() => $q1, $q2->getSerialization() => $q2 ], $mode )
			->willReturn( [
				$q1->getSerialization() => (object)[
					'page_latest' => 100,
					'page_is_redirect' => false,
				],
				$q2->getSerialization() => (object)[
					'page_latest' => 200,
					'page_is_redirect' => false,
				],
			] );
		$lookup->expects( $this->never() )
			->method( 'loadLatestRevisionIds' );
		$accessor = new PrefetchingWikiPageEntityMetaDataAccessor( $lookup, new NullLogger() );

		$accessor->prefetch( [ $q1, $q2 ] );
		$latestRevisionIds = $accessor->loadLatestRevisionIds( [ $q2 ], $mode );

		$this->assertSame( [ $q2->getSerialization() => 200 ], $latestRevisionIds );
	}

	public function testLoadLatestRevisionIds_noResultForRedirect() {
		$mode = LookupConstants::LATEST_FROM_REPLICA;
		$q1 = new ItemId( 'Q1' );

		$lookup = $this->createMock( WikiPageEntityMetaDataAccessor::class );
		$lookup->expects( $this->once() )
			->method( 'loadRevisionInformation' )
			->with( [ $q1->getSerialization() => $q1 ], $mode )
			->willReturn( [ $q1->getSerialization() => (object)[
				'page_latest' => 100,
				'page_is_redirect' => true,
			] ] );
		$lookup->expects( $this->never() )
			->method( 'loadLatestRevisionIds' );
		$accessor = new PrefetchingWikiPageEntityMetaDataAccessor( $lookup, new NullLogger() );

		$accessor->prefetch( [ $q1 ] );
		$latestRevisionIds = $accessor->loadLatestRevisionIds( [ $q1 ], $mode );

		$this->assertSame( [ $q1->getSerialization() => false ], $latestRevisionIds );
	}

	/**
	 * Makes sure that calling $method with $params will purge the cache
	 * for Q1.
	 *
	 * @param string $method
	 * @param array $params
	 */
	private function purgeMethodTest( $method, array $params ) {
		$fromReplica = LookupConstants::LATEST_FROM_REPLICA;
		$q1 = new ItemId( 'Q1' );

		$lookup = $this->createMock( WikiPageEntityMetaDataAccessor::class );
		$lookup->expects( $this->exactly( 2 ) )
			->method( 'loadRevisionInformation' )
			->with( [ $q1->getSerialization() => $q1 ] )
			->willReturnCallback( function( array $entityIds ) {
				static $firstCall = true;
				if ( $firstCall ) {
					$firstCall = false;
					return [ 'Q1' => 'Foo' ];
				} else {
					return [ 'Q1' => 'Bar' ];
				}
			} );

		$accessor = new PrefetchingWikiPageEntityMetaDataAccessor( $lookup, new NullLogger() );

		$rows = $accessor->loadRevisionInformation( [ $q1 ], $fromReplica );
		$result = $rows[$q1->getSerialization()];

		$this->assertSame( 'Foo', $result );

		$accessor->$method( ...$params );

		// Load it again after purge
		$rows = $accessor->loadRevisionInformation( [ $q1 ], $fromReplica );
		$result = $rows[$q1->getSerialization()];

		$this->assertSame( 'Bar', $result );
	}

	public function testPurge() {
		$this->purgeMethodTest( 'purge', [ new ItemId( 'Q1' ) ] );
	}

	public function testEntityDeleted() {
		$this->purgeMethodTest( 'entityDeleted', [ new ItemId( 'Q1' ) ] );
	}

	public function testEntityUpdated() {
		$entityRevision = new EntityRevision(
			new Item( new ItemId( 'Q1' ) ),
			123
		);

		$this->purgeMethodTest( 'entityUpdated', [ $entityRevision ] );
	}

	public function testRedirectUpdated() {
		$entityRedirect = new EntityRedirect(
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' )
		);

		$this->purgeMethodTest( 'redirectUpdated', [ $entityRedirect, 123 ] );
	}

}

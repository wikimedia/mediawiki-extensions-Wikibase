<?php

namespace Wikibase\DataAccess\Tests;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers \Wikibase\DataAccess\Tests\EntityPrefetcherSpy
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityPrefetcherSpyTest extends TestCase {

	public function testEntityIdsPassedToPrefetchAreRecorded() {
		$idOne = new ItemId( 'Q1' );
		$idTwo = new ItemId( 'Q100' );
		$idThree = new ItemId( 'Q123' );

		$prefetcher = new EntityPrefetcherSpy();

		$prefetcher->prefetch( [ $idOne, $idTwo ] );
		$prefetcher->prefetch( [ $idThree ] );

		$this->assertEquals( [ $idOne, $idTwo, $idThree ], $prefetcher->getPrefetchedEntities() );
	}

	public function testEntityIdIsOnlyRecordedOnceOnMultiplePrefetchCalls() {
		$idOne = new ItemId( 'Q1' );
		$idTwo = new ItemId( 'Q100' );

		$prefetcher = new EntityPrefetcherSpy();

		$prefetcher->prefetch( [ $idOne, $idTwo ] );
		$prefetcher->prefetch( [ $idOne ] );

		$this->assertEquals( [ $idOne, $idTwo ], $prefetcher->getPrefetchedEntities() );
	}

	public function testPurgedEntitiesAreNotStoredAnymore() {
		$idOne = new ItemId( 'Q1' );
		$idTwo = new ItemId( 'Q100' );

		$prefetcher = new EntityPrefetcherSpy();

		$prefetcher->prefetch( [ $idOne, $idTwo ] );
		$prefetcher->purge( $idOne );

		$this->assertEquals( [ $idTwo ], $prefetcher->getPrefetchedEntities() );
	}

	public function testPurgeAllClearsTheWholeBuffer() {
		$idOne = new ItemId( 'Q1' );
		$idTwo = new ItemId( 'Q100' );

		$prefetcher = new EntityPrefetcherSpy();

		$prefetcher->prefetch( [ $idOne, $idTwo ] );
		$prefetcher->purgeAll();

		$this->assertSame( [], $prefetcher->getPrefetchedEntities() );
	}

}

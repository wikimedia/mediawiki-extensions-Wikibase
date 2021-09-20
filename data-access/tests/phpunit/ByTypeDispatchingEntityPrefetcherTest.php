<?php

namespace Wikibase\DataAccess\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\ByTypeDispatchingEntityPrefetcher;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;

/**
 * @covers \Wikibase\DataAccess\ByTypeDispatchingEntityPrefetcher
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ByTypeDispatchingEntityPrefetcherTest extends TestCase {

	public function testGivenNotEntityInfoBuilderInstance_constructorThrowsException() {
		$this->expectException( InvalidArgumentException::class );
		new ByTypeDispatchingEntityPrefetcher( [ 'item' => 'FOOBAR' ] );
	}

	public function testGivenNotStringIndexedArray_constructorThrowsException() {
		$this->expectException( InvalidArgumentException::class );
		new ByTypeDispatchingEntityPrefetcher( [ new EntityPrefetcherSpy() ] );
	}

	public function testPrefetchFetchesDataOfAllEntitiesOfKnownType() {
		$itemId = new ItemId( 'Q123' );
		$propertyId = new NumericPropertyId( 'P100' );

		$itemPrefetcher = new EntityPrefetcherSpy();
		$propertyPrefetcher = new EntityPrefetcherSpy();

		$prefetcher = new ByTypeDispatchingEntityPrefetcher( [
			'item' => $itemPrefetcher,
			'property' => $propertyPrefetcher,
		] );

		$prefetcher->prefetch( [ $itemId, $propertyId ] );

		$this->assertEquals(
			[ $itemId, $propertyId ],
			array_merge( $itemPrefetcher->getPrefetchedEntities(), $propertyPrefetcher->getPrefetchedEntities() )
		);
	}

	public function testPrefetchOmitsEntitiesOfKnownType() {
		$itemId = new ItemId( 'Q123' );
		$propertyId = new NumericPropertyId( 'P100' );

		$innerPrefetcher = new EntityPrefetcherSpy();

		$prefetcher = new ByTypeDispatchingEntityPrefetcher( [
			'item' => $innerPrefetcher,
		] );

		$prefetcher->prefetch( [ $itemId, $propertyId ] );

		$this->assertNotContains( $propertyId, $innerPrefetcher->getPrefetchedEntities() );
	}

	public function testPurge() {
		$itemId = new ItemId( 'Q123' );
		$propertyId = new NumericPropertyId( 'P100' );

		$itemPrefetcher = new EntityPrefetcherSpy();
		$propertyPrefetcher = new EntityPrefetcherSpy();

		$prefetcher = new ByTypeDispatchingEntityPrefetcher( [
			'item' => $itemPrefetcher,
			'property' => $propertyPrefetcher,
		] );

		$prefetcher->prefetch( [ $itemId, $propertyId ] );

		$prefetcher->purge( $itemId );

		$this->assertEquals(
			[ $propertyId ],
			array_merge( $itemPrefetcher->getPrefetchedEntities(), $propertyPrefetcher->getPrefetchedEntities() )
		);
	}

	public function testPurgeAll() {
		$itemId = new ItemId( 'Q123' );
		$propertyId = new NumericPropertyId( 'P100' );

		$itemPrefetcher = new EntityPrefetcherSpy();
		$propertyPrefetcher = new EntityPrefetcherSpy();

		$prefetcher = new ByTypeDispatchingEntityPrefetcher( [
			'item' => $itemPrefetcher,
			'property' => $propertyPrefetcher,
		] );

		$prefetcher->prefetch( [ $itemId, $propertyId ] );

		$prefetcher->purgeAll();

		$this->assertSame( [], $itemPrefetcher->getPrefetchedEntities() );
		$this->assertSame( [], $propertyPrefetcher->getPrefetchedEntities() );
	}

}

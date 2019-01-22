<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\ByTypeDispatchingEntityPrefetcher;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @covers \Wikibase\DataAccess\ByTypeDispatchingEntityPrefetcher
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ByTypeDispatchingEntityPrefetcherTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testGivenNotEntityInfoBuilderInstance_constructorThrowsException() {
		new ByTypeDispatchingEntityPrefetcher( [ 'item' => 'FOOBAR' ] );
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testGivenNotStringIndexedArray_constructorThrowsException() {
		new ByTypeDispatchingEntityPrefetcher( [ new EntityPrefetcherSpy() ] );
	}

	public function testPrefetchFetchesDataOfAllEntitiesOfKnownType() {
		$itemId = new ItemId( 'Q123' );
		$propertyId = new PropertyId( 'P100' );

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
		$propertyId = new PropertyId( 'P100' );

		$innerPrefetcher = new EntityPrefetcherSpy();

		$prefetcher = new ByTypeDispatchingEntityPrefetcher( [
			'item' => $innerPrefetcher,
		] );

		$prefetcher->prefetch( [ $itemId, $propertyId ] );

		$this->assertNotContains( $propertyId, $innerPrefetcher->getPrefetchedEntities() );
	}

	public function testPurge() {
		$itemId = new ItemId( 'Q123' );
		$propertyId = new PropertyId( 'P100' );

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
		$propertyId = new PropertyId( 'P100' );

		$itemPrefetcher = new EntityPrefetcherSpy();
		$propertyPrefetcher = new EntityPrefetcherSpy();

		$prefetcher = new ByTypeDispatchingEntityPrefetcher( [
			'item' => $itemPrefetcher,
			'property' => $propertyPrefetcher,
		] );

		$prefetcher->prefetch( [ $itemId, $propertyId ] );

		$prefetcher->purgeAll();

		$this->assertEmpty( array_merge( $itemPrefetcher->getPrefetchedEntities(), $propertyPrefetcher->getPrefetchedEntities() ) );
	}

}

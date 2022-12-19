<?php

namespace Wikibase\DataAccess\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\ByTypeDispatchingEntityRevisionLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LookupConstants;

/**
 * @covers \Wikibase\DataAccess\ByTypeDispatchingEntityRevisionLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ByTypeDispatchingEntityRevisionLookupTest extends TestCase {

	public function testGivenNotEntityRevisionLookupInstance_constructorThrowsException() {
		$this->expectException( InvalidArgumentException::class );
		new ByTypeDispatchingEntityRevisionLookup( [ 'item' => 'FOOBAR' ] );
	}

	public function testGivenNotStringIndexedArray_constructorThrowsException() {
		$this->expectException( InvalidArgumentException::class );
		new ByTypeDispatchingEntityRevisionLookup( [ $this->createMock( EntityRevisionLookup::class ) ] );
	}

	public function testGivenEntityIdOfKnownType_getEntityRevisionDispatchesRequestToRelevantLookup() {
		$itemId = new ItemId( 'Q1' );
		$revId = 123;
		$mode = LookupConstants::LATEST_FROM_REPLICA;

		$itemLookup = $this->createMock( EntityRevisionLookup::class );
		$itemLookup->expects( $this->atLeastOnce() )
			->method( 'getEntityRevision' )
			->with( $itemId, $revId, $mode );

		$propertyLookup = $this->createMock( EntityRevisionLookup::class );
		$propertyLookup->expects( $this->never() )
			->method( 'getEntityRevision' );

		$lookup = new ByTypeDispatchingEntityRevisionLookup( [
			'item' => $itemLookup,
			'property' => $propertyLookup,
		] );

		$lookup->getEntityRevision( $itemId, $revId, $mode );
	}

	public function testGivenEntityIdOfKnownType_getEntityRevisionReturnsResultFromRelevantLookup() {
		$expectedResult = 'Item Lookup Result';
		$itemLookup = $this->createMock( EntityRevisionLookup::class );
		$itemLookup->method( 'getEntityRevision' )
			->willReturn( $expectedResult );

		$propertyLookup = $this->createMock( EntityRevisionLookup::class );

		$lookup = new ByTypeDispatchingEntityRevisionLookup( [
			'item' => $itemLookup,
			'property' => $propertyLookup,
		] );

		$this->assertEquals(
			$expectedResult,
			$lookup->getEntityRevision( new ItemId( 'Q1' ) )
		);
	}

	public function testGivenUnknownEntityIdType_getEntityRevisionReturnsNull() {
		$itemLookup = $this->createMock( EntityRevisionLookup::class );

		$lookup = new ByTypeDispatchingEntityRevisionLookup( [
			'item' => $itemLookup,
		] );

		$this->assertNull( $lookup->getEntityRevision( new NumericPropertyId( 'P1' ) ) );
	}

	public function testGivenEntityIdOfKnownType_getLatestRevisionIdDispatchesRequestToRelevantLookup() {
		$itemId = new ItemId( 'Q1' );

		$itemLookup = $this->createMock( EntityRevisionLookup::class );
		$itemLookup->expects( $this->atLeastOnce() )
			->method( 'getLatestRevisionId' )
			->with( $itemId );

		$propertyLookup = $this->createMock( EntityRevisionLookup::class );
		$propertyLookup->expects( $this->never() )
			->method( 'getLatestRevisionId' );

		$lookup = new ByTypeDispatchingEntityRevisionLookup( [
			'item' => $itemLookup,
			'property' => $propertyLookup,
		] );

		$lookup->getLatestRevisionId( $itemId );
	}

	public function testGivenEntityIdOfKnownType_getLatestRevisionIdReturnsResultFromRelevantLookup() {
		$expectedResult = 'Item Lookup Result';
		$itemLookup = $this->createMock( EntityRevisionLookup::class );
		$itemLookup->method( 'getLatestRevisionId' )
			->willReturn( $expectedResult );

		$propertyLookup = $this->createMock( EntityRevisionLookup::class );

		$lookup = new ByTypeDispatchingEntityRevisionLookup( [
			'item' => $itemLookup,
			'property' => $propertyLookup,
		] );

		$this->assertEquals(
			$expectedResult,
			$lookup->getLatestRevisionId( new ItemId( 'Q1' ) )
		);
	}

	public function testGivenUnknownEntityIdType_getLatestRevisionIdReturnsNonExistentEntity() {
		$itemLookup = $this->createMock( EntityRevisionLookup::class );

		$lookup = new ByTypeDispatchingEntityRevisionLookup( [
			'item' => $itemLookup,
		] );

		$shouldNotBeCalled = function () {
			$this->fail( 'Should not be called' );
		};
		$returnTrue = function () {
			return true;
		};

		$nonExistentEntityReturned = $lookup->getLatestRevisionId( new NumericPropertyId( 'P1' ) )
			->onConcreteRevision( $shouldNotBeCalled )
			->onRedirect( $shouldNotBeCalled )
			->onNonexistentEntity( $returnTrue )
			->map();

		$this->assertTrue( $nonExistentEntityReturned );
	}

}

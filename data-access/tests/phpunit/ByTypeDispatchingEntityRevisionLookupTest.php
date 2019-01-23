<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\ByTypeDispatchingEntityRevisionLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityRevisionLookup;

/**
 * @covers \Wikibase\DataAccess\ByTypeDispatchingEntityRevisionLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ByTypeDispatchingEntityRevisionLookupTest extends \PHPUnit_Framework_TestCase {

	public function testGivenInvalidArguments_constructorThrowsException() {
		$this->markTestIncomplete( 'TODO' );
	}

	public function testGivenEntityIdOfKnownType_getEntityRevisionDispatchesRequestToRelevantLookup() {
		$itemId = new ItemId( 'Q1' );
		$revId = 123;
		$mode = EntityRevisionLookup::LATEST_FROM_REPLICA;

		$itemLookup = $this->createMock( EntityRevisionLookup::class );
		$itemLookup->expects( $this->atLeastOnce() )
			->method( 'getEntityRevision' )
			->with( $itemId, $revId, $mode );

		$propertyLookup = $this->createMock( EntityRevisionLookup::class );
		$propertyLookup->expects( $this->never() )
			->method( 'getEntityRevision' );

		$lookup = new ByTypeDispatchingEntityRevisionLookup( [
			'item' => $itemLookup,
			'property' => $propertyLookup
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
			'property' => $propertyLookup
		] );

		$this->assertEquals(
			$expectedResult,
			$lookup->getEntityRevision( new ItemId( 'Q1' ) )
		);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testGivenUnknownEntityIdType_getEntityRevisionThrowsException() {
		$itemLookup = $this->createMock( EntityRevisionLookup::class );

		$lookup = new ByTypeDispatchingEntityRevisionLookup( [
			'item' => $itemLookup
		] );

		$lookup->getEntityRevision( new PropertyId( 'P1' ) );
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
			'property' => $propertyLookup
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
			'property' => $propertyLookup
		] );

		$this->assertEquals(
			$expectedResult,
			$lookup->getLatestRevisionId( new ItemId( 'Q1' ) )
		);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testGivenUnknownEntityIdType_getLatestRevisionIdThrowsException() {
		$itemLookup = $this->createMock( EntityRevisionLookup::class );

		$lookup = new ByTypeDispatchingEntityRevisionLookup( [
			'item' => $itemLookup
		] );

		$lookup->getLatestRevisionId( new PropertyId( 'P1' ) );
	}

}

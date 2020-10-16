<?php

namespace Wikibase\DataModel\Tests\Entity;

use Traversable;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdSet;

/**
 * @covers \Wikibase\DataModel\Entity\ItemIdSet
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemIdSetTest extends \PHPUnit\Framework\TestCase {

	public function testGetIterator() {
		$set = new ItemIdSet();
		$this->assertInstanceOf( Traversable::class, $set->getIterator() );
	}

	/**
	 * @dataProvider serializationsProvider
	 */
	public function testGetSerializations( array $itemIds, array $expected ) {
		$set = new ItemIdSet( $itemIds );
		$this->assertSame( $expected, $set->getSerializations() );
	}

	public function serializationsProvider() {
		return [
			[ [], [] ],
			[ [ new ItemId( 'Q1' ) ], [ 'Q1' ] ],
			[ [ new ItemId( 'Q1' ), new ItemId( 'Q2' ) ], [ 'Q1', 'Q2' ] ],
		];
	}

	public function testGivenEmptySet_countReturnsZero() {
		$set = new ItemIdSet();
		$this->assertSame( 0, $set->count() );
	}

	public function testGivenSetWithTwoItems_countReturnsTwo() {
		$set = new ItemIdSet( [
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
		] );
		$this->assertSame( 2, $set->count() );
	}

	public function testGivenNotSetId_hasReturnsFalse() {
		$set = new ItemIdSet( [ new ItemId( 'Q1337' ) ] );
		$this->assertFalse( $set->has( new ItemId( 'Q1' ) ) );
	}

	public function testGivenSetId_hasReturnsTrue() {
		$set = new ItemIdSet( [ new ItemId( 'Q1337' ) ] );
		$this->assertTrue( $set->has( new ItemId( 'Q1337' ) ) );
	}

	public function testCanIterateOverSet() {
		$array = [
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
		];

		$set = new ItemIdSet( $array );
		$this->assertEquals( $array, array_values( iterator_to_array( $set ) ) );
	}

	public function testGivenDuplicates_noLongerPresentInIteration() {
		$array = [
			new ItemId( 'Q1' ),
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
		];

		$set = new ItemIdSet( $array );

		$this->assertEquals(
			[
				new ItemId( 'Q1' ),
				new ItemId( 'Q2' ),
			],
			array_values( iterator_to_array( $set ) )
		);
	}

	/**
	 * @dataProvider itemIdSetProvider
	 */
	public function testGivenTheSameSet_equalsReturnsTrue( ItemIdSet $set ) {
		$this->assertTrue( $set->equals( $set ) );
		$this->assertTrue( $set->equals( clone $set ) );
	}

	public function itemIdSetProvider() {
		return [
			[
				new ItemIdSet(),
			],

			[
				new ItemIdSet( [
					new ItemId( 'Q1' ),
				] ),
			],

			[
				new ItemIdSet( [
					new ItemId( 'Q1' ),
					new ItemId( 'Q2' ),
					new ItemId( 'Q3' ),
				] ),
			],
		];
	}

	public function testGivenNonItemIdSet_equalsReturnsFalse() {
		$set = new ItemIdSet();
		$this->assertFalse( $set->equals( null ) );
		$this->assertFalse( $set->equals( new \stdClass() ) );
	}

	public function testGivenDifferentSet_equalsReturnsFalse() {
		$setOne = new ItemIdSet( [
			new ItemId( 'Q1337' ),
			new ItemId( 'Q1' ),
			new ItemId( 'Q42' ),
		] );

		$setTwo = new ItemIdSet( [
			new ItemId( 'Q1337' ),
			new ItemId( 'Q2' ),
			new ItemId( 'Q42' ),
		] );

		$this->assertFalse( $setOne->equals( $setTwo ) );
	}

	public function testGivenSetWithDifferentOrder_equalsReturnsTrue() {
		$setOne = new ItemIdSet( [
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
			new ItemId( 'Q3' ),
		] );

		$setTwo = new ItemIdSet( [
			new ItemId( 'Q2' ),
			new ItemId( 'Q3' ),
			new ItemId( 'Q1' ),
		] );

		$this->assertTrue( $setOne->equals( $setTwo ) );
	}

	public function testGivenDifferentSizedSets_equalsReturnsFalse() {
		$small = new ItemIdSet( [
			new ItemId( 'Q1' ),
		] );

		$big = new ItemIdSet( [
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
			new ItemId( 'Q3' ),
		] );

		$this->assertFalse( $small->equals( $big ) );
		$this->assertFalse( $big->equals( $small ) );
	}

}

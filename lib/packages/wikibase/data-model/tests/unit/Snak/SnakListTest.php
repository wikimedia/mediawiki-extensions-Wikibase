<?php

namespace Wikibase\DataModel\Tests\Snak;

use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers \Wikibase\DataModel\Snak\SnakList
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Addshore
 * @author Thiemo Kreuz
 */
class SnakListTest extends \PHPUnit\Framework\TestCase {

	public function elementInstancesProvider() {
		$id42 = new NumericPropertyId( 'P42' );

		$argLists = [];

		$argLists[] = [ [ new PropertyNoValueSnak( $id42 ) ] ];
		$argLists[] = [ [ new PropertyNoValueSnak( new NumericPropertyId( 'P9001' ) ) ] ];
		$argLists[] = [ [ new PropertyValueSnak( $id42, new StringValue( 'a' ) ) ] ];

		return $argLists;
	}

	public function instanceProvider() {
		$id42 = new NumericPropertyId( 'P42' );
		$id9001 = new NumericPropertyId( 'P9001' );

		return [
			[ new SnakList() ],
			[ new SnakList( [
				new PropertyNoValueSnak( $id42 ),
			] ) ],
			[ new SnakList( [
				new PropertyNoValueSnak( $id42 ),
				new PropertyNoValueSnak( $id9001 ),
			] ) ],
			[ new SnakList( [
				new PropertyNoValueSnak( $id42 ),
				new PropertyNoValueSnak( $id9001 ),
				new PropertyValueSnak( $id42, new StringValue( 'a' ) ),
			] ) ],
		];
	}

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 */
	public function testGivenInvalidConstructorArguments_constructorThrowsException( $input ) {
		$this->expectException( InvalidArgumentException::class );
		new SnakList( $input );
	}

	public function invalidConstructorArgumentsProvider() {
		$id1 = new NumericPropertyId( 'P1' );

		return [
			[ null ],
			[ false ],
			[ 1 ],
			[ 0.1 ],
			[ 'string' ],
			[ $id1 ],
			[ new PropertyNoValueSnak( $id1 ) ],
			[ new PropertyValueSnak( $id1, new StringValue( 'a' ) ) ],
			[ [ null ] ],
			[ [ $id1 ] ],
			[ [ new SnakList() ] ],
		];
	}

	public function testGivenAssociativeArray_constructorPreservesArrayKeys() {
		$snakList = new SnakList( [ 'key' => new PropertyNoValueSnak( 1 ) ] );
		$this->assertSame( [ 'key' ], array_keys( iterator_to_array( $snakList ) ) );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param SnakList $array
	 */
	public function testHasSnak( SnakList $array ) {
		/**
		 * @var Snak $hashable
		 */
		foreach ( iterator_to_array( $array ) as $hashable ) {
			$this->assertTrue( $array->hasSnak( $hashable ) );
			$this->assertTrue( $array->hasSnakHash( $hashable->getHash() ) );
			$array->removeSnak( $hashable );
			$this->assertFalse( $array->hasSnak( $hashable ) );
			$this->assertFalse( $array->hasSnakHash( $hashable->getHash() ) );
		}

		$this->assertTrue( true );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param SnakList $array
	 */
	public function testRemoveSnak( SnakList $array ) {
		$elementCount = $array->count();

		/**
		 * @var Snak $element
		 */
		foreach ( iterator_to_array( $array ) as $element ) {
			$this->assertTrue( $array->hasSnak( $element ) );

			if ( $elementCount % 2 === 0 ) {
				$array->removeSnak( $element );
			} else {
				$array->removeSnakHash( $element->getHash() );
			}

			$this->assertFalse( $array->hasSnak( $element ) );
			$this->assertSame( --$elementCount, $array->count() );
		}

		$element = new PropertyNoValueSnak( new NumericPropertyId( 'P42' ) );

		$array->removeSnak( $element );
		$array->removeSnakHash( $element->getHash() );

		$this->assertTrue( true );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param SnakList $array
	 */
	public function testAddSnak( SnakList $array ) {
		$elementCount = $array->count();

		$element = $this->elementInstancesProvider()[0][0][0];

		if ( !$array->hasSnak( $element ) ) {
			++$elementCount;
		}

		$this->assertSame( !$array->hasSnak( $element ), $array->addSnak( $element ) );

		$this->assertSame( $elementCount, $array->count() );

		$this->assertFalse( $array->addSnak( $element ) );

		$this->assertSame( $elementCount, $array->count() );
	}

	public function orderByPropertyProvider() {
		$id1 = new NumericPropertyId( 'P1' );
		$id2 = new NumericPropertyId( 'P2' );
		$id3 = new NumericPropertyId( 'P3' );
		$id4 = new NumericPropertyId( 'P4' );

		/**
		 * List of test data containing snaks to initialize SnakList objects. The first list of
		 * snaks represents the snak list to be used as test input while the second represents the
		 * expected result.
		 * @var array
		 */
		$rawArguments = [
			'Default order' => [
				[],
				[],
			],
			'Unknown id in order' => [
				[],
				[],
				[ 'P1' ],
			],
			[
				[ new PropertyNoValueSnak( $id1 ) ],
				[ new PropertyNoValueSnak( $id1 ) ],
			],
			[
				[
					new PropertyNoValueSnak( $id2 ),
					new PropertyNoValueSnak( $id1 ),
				],
				[
					new PropertyNoValueSnak( $id2 ),
					new PropertyNoValueSnak( $id1 ),
				],
			],
			[
				[
					new PropertyNoValueSnak( $id1 ),
					new PropertyNoValueSnak( $id2 ),
					new PropertyValueSnak( $id1, new StringValue( 'a' ) ),
				],
				[
					new PropertyNoValueSnak( $id1 ),
					new PropertyValueSnak( $id1, new StringValue( 'a' ) ),
					new PropertyNoValueSnak( $id2 ),
				],
			],
			'With additional order' => [
				[
					new PropertyNoValueSnak( $id3 ),
					new PropertyNoValueSnak( $id2 ),
					new PropertyValueSnak( $id1, new StringValue( 'a' ) ),
				],
				[
					new PropertyNoValueSnak( $id2 ),
					new PropertyNoValueSnak( $id3 ),
					new PropertyValueSnak( $id1, new StringValue( 'a' ) ),
				],
				[ 'P2' ],
			],
			[
				[
					new PropertyNoValueSnak( $id3 ),
					new PropertyNoValueSnak( $id2 ),
					new PropertyNoValueSnak( $id2 ),
					new PropertyValueSnak( $id1, new StringValue( 'a' ) ),
					new PropertyNoValueSnak( $id1 ),
				],
				[
					new PropertyValueSnak( $id1, new StringValue( 'a' ) ),
					new PropertyNoValueSnak( $id1 ),
					new PropertyNoValueSnak( $id3 ),
					new PropertyNoValueSnak( $id2 ),
					new PropertyNoValueSnak( $id2 ),
				],
				[ 'P1' ],
			],
			'Multiple IDs in order' => [
				[
					new PropertyValueSnak( $id1, new StringValue( 'a' ) ),
					new PropertyValueSnak( $id2, new StringValue( 'b' ) ),
					new PropertyValueSnak( $id1, new StringValue( 'c' ) ),
					new PropertyValueSnak( $id3, new StringValue( 'd' ) ),
					new PropertyValueSnak( $id4, new StringValue( 'e' ) ),
					new PropertyValueSnak( $id2, new StringValue( 'f' ) ),
					new PropertyValueSnak( $id4, new StringValue( 'g' ) ),
				],
				[
					new PropertyValueSnak( $id2, new StringValue( 'b' ) ),
					new PropertyValueSnak( $id2, new StringValue( 'f' ) ),
					new PropertyValueSnak( $id3, new StringValue( 'd' ) ),
					new PropertyValueSnak( $id1, new StringValue( 'a' ) ),
					new PropertyValueSnak( $id1, new StringValue( 'c' ) ),
					new PropertyValueSnak( $id4, new StringValue( 'e' ) ),
					new PropertyValueSnak( $id4, new StringValue( 'g' ) ),
				],
				[ 'P2', 'P3', 'P1' ],
			],
		];

		$arguments = [];

		foreach ( $rawArguments as $key => $rawArgument ) {
			$arguments[$key] = [
				new SnakList( $rawArgument[0] ),
				new SnakList( $rawArgument[1] ),
				array_key_exists( 2, $rawArgument ) ? $rawArgument[2] : [],
			];
		}

		return $arguments;
	}

	/**
	 * @dataProvider orderByPropertyProvider
	 */
	public function testOrderByProperty( SnakList $snakList, SnakList $expected, array $order = [] ) {
		$initialSnakList = new SnakList( array_values( iterator_to_array( $snakList ) ) );

		$snakList->orderByProperty( $order );

		// Instantiate new SnakList resetting the snaks' array keys. This allows comparing the
		// reordered SnakList to the expected SnakList.
		$orderedSnakList = new SnakList( array_values( iterator_to_array( $snakList ) ) );

		$this->assertEquals( $expected, $orderedSnakList );

		if ( $orderedSnakList->equals( $initialSnakList ) ) {
			$this->assertSame( $initialSnakList->getHash(), $snakList->getHash() );
		} else {
			$this->assertNotSame( $initialSnakList->getHash(), $snakList->getHash() );
		}

		/** @var Snak $snak */
		foreach ( $snakList as $snak ) {
			$hash = $snak->getHash();
			$this->assertSame(
				$hash,
				$snakList->getSnak( $hash )->getHash(),
				'Reordering must not mess up the lists internal state'
			);
		}
	}

	/**
	 * @dataProvider equalsProvider
	 */
	public function testEquals( SnakList $list1, SnakList $list2, $expected ) {
		$this->assertSame( $expected, $list1->equals( $list2 ) );
	}

	public function equalsProvider() {
		$empty = new SnakList();
		$oneSnak = new SnakList( [ new PropertyNoValueSnak( 1 ) ] );

		return [
			'empty object is equal to itself' => [
				$empty,
				$empty,
				true,
			],
			'non-empty object is equal to itself' => [
				$oneSnak,
				$oneSnak,
				true,
			],
			'different empty objects are equal' => [
				$empty,
				new SnakList(),
				true,
			],
			'different objects with same content are equal' => [
				$oneSnak,
				new SnakList( [ new PropertyNoValueSnak( 1 ) ] ),
				true,
			],
			'different objects with different content are not equal' => [
				$oneSnak,
				new SnakList( [ new PropertyNoValueSnak( 2 ) ] ),
				false,
			],
		];
	}

	public function testGetHash() {
		$snakList = new SnakList( [ new PropertyNoValueSnak( 1 ) ] );
		$hash = $snakList->getHash();

		$this->assertIsString( $hash, 'must be a string' );
		$this->assertNotSame( '', $hash, 'must not be empty' );
		$this->assertSame( $hash, $snakList->getHash(), 'second call must return the same hash' );

		$otherList = new SnakList( [ new PropertyNoValueSnak( 2 ) ] );
		$this->assertNotSame( $hash, $otherList->getHash() );
	}

	/**
	 * This integration test (relies on SnakObject::getHash) is supposed to break whenever the hash
	 * calculation changes.
	 */
	public function testHashStability() {
		$snakList = new SnakList();
		$this->assertSame( 'da39a3ee5e6b4b0d3255bfef95601890afd80709', $snakList->getHash() );

		$snakList = new SnakList( [ new PropertyNoValueSnak( 1 ) ] );
		$this->assertSame( '4327ac5109aaf437ccce05580c563a5857d96c82', $snakList->getHash() );
	}

	/**
	 * @dataProvider provideEqualSnakLists
	 */
	public function testGivenEqualSnakLists_getHashIsTheSame( SnakList $self, SnakList $other ) {
		$this->assertSame( $self->getHash(), $other->getHash() );
	}

	public function provideEqualSnakLists() {
		$empty = new SnakList();
		$oneSnak = new SnakList( [ new PropertyNoValueSnak( 1 ) ] );

		return [
			'same empty object' => [ $empty, $empty ],
			'same non-empty object' => [ $oneSnak, $oneSnak ],
			'equal empty objects' => [ $empty, new SnakList() ],
			'equal non-empty objects' => [ $oneSnak, new SnakList( [ new PropertyNoValueSnak( 1 ) ] ) ],
		];
	}

}

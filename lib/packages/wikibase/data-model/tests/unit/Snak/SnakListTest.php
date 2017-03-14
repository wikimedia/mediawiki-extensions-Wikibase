<?php

namespace Wikibase\DataModel\Tests\Snak;

use DataValues\StringValue;
use Hashable;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Tests\HashArray\HashArrayTest;

/**
 * @covers Wikibase\DataModel\Snak\SnakList
 * @uses DataValues\StringValue
 * @uses Wikibase\DataModel\Entity\PropertyId
 * @uses Wikibase\DataModel\Snak\PropertyNoValueSnak
 * @uses Wikibase\DataModel\Snak\PropertyValueSnak
 * @uses Wikibase\DataModel\Snak\Snak
 * @uses Wikibase\DataModel\Snak\SnakList
 * @uses Wikibase\DataModel\HashArray
 * @uses Wikibase\DataModel\Snak\SnakObject
 * @uses Wikibase\DataModel\Internal\MapValueHasher
 * @uses Wikibase\DataModel\Entity\EntityId
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Addshore
 * @author Thiemo Mättig
 */
class SnakListTest extends HashArrayTest {

	/**
	 * @see HashArrayTest::getInstanceClass
	 */
	public function getInstanceClass() {
		return 'Wikibase\DataModel\Snak\SnakList';
	}

	public function elementInstancesProvider() {
		$id42 = new PropertyId( 'P42' );

		$argLists = [];

		$argLists[] = [ [ new PropertyNoValueSnak( $id42 ) ] ];
		$argLists[] = [ [ new PropertyNoValueSnak( new PropertyId( 'P9001' ) ) ] ];
		$argLists[] = [ [ new PropertyValueSnak( $id42, new StringValue( 'a' ) ) ] ];

		return $argLists;
	}

	public function constructorProvider() {
		$id42 = new PropertyId( 'P42' );
		$id9001 = new PropertyId( 'P9001' );

		return [
			[],
			[ [] ],
			[ [
				new PropertyNoValueSnak( $id42 )
			] ],
			[ [
				new PropertyNoValueSnak( $id42 ),
				new PropertyNoValueSnak( $id9001 ),
			] ],
			[ [
				new PropertyNoValueSnak( $id42 ),
				new PropertyNoValueSnak( $id9001 ),
				new PropertyValueSnak( $id42, new StringValue( 'a' ) ),
			] ],
		];
	}

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenInvalidConstructorArguments_constructorThrowsException( $input ) {
		new SnakList( $input );
	}

	public function invalidConstructorArgumentsProvider() {
		$id1 = new PropertyId( 'P1' );

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
			}
			else {
				$array->removeSnakHash( $element->getHash() );
			}

			$this->assertFalse( $array->hasSnak( $element ) );
			$this->assertEquals( --$elementCount, $array->count() );
		}

		$element = new PropertyNoValueSnak( new PropertyId( 'P42' ) );

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

		$elements = $this->elementInstancesProvider();
		$element = array_shift( $elements );
		$element = $element[0][0];

		if ( !$array->hasSnak( $element ) ) {
			++$elementCount;
		}

		$this->assertEquals( !$array->hasSnak( $element ), $array->addSnak( $element ) );

		$this->assertEquals( $elementCount, $array->count() );

		$this->assertFalse( $array->addSnak( $element ) );

		$this->assertEquals( $elementCount, $array->count() );
	}

	public function orderByPropertyProvider() {
		$class = $this->getInstanceClass();

		$id1 = new PropertyId( 'P1' );
		$id2 = new PropertyId( 'P2' );
		$id3 = new PropertyId( 'P3' );

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
				[ 'P1' ]
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
				[ 'P2' ]
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
				[ 'P1' ]
			],
		];

		$arguments = [];

		foreach ( $rawArguments as $key => $rawArgument ) {
			$arguments[$key] = [
				new $class( $rawArgument[0] ),
				new $class( $rawArgument[1] ),
				array_key_exists( 2, $rawArgument ) ? $rawArgument[2] : []
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
	}

	public function testHashableInterface() {
		$this->assertInstanceOf( Hashable::class, new SnakList() );
	}

	public function testGetHash() {
		$snakList = new SnakList( [ new PropertyNoValueSnak( 1 ) ] );
		$hash = $snakList->getHash();

		$this->assertInternalType( 'string', $hash, 'must be a string' );
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

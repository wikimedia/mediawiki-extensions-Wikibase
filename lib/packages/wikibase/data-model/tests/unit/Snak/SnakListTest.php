<?php

namespace Wikibase\Test\Snak;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\Test\HashArray\HashArrayTest;

/**
 * @covers Wikibase\DataModel\Snak\SnakList
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Adam Shorland
 */
class SnakListTest extends HashArrayTest {

	/**
	 * @see GenericArrayObjectTest::getInstanceClass
	 */
	public function getInstanceClass() {
		return '\Wikibase\SnakList';
	}

	/**
	 * @see GenericArrayObjectTest::elementInstancesProvider
	 */
	public function elementInstancesProvider() {
		$id42 = new PropertyId( 'P42' );

		$argLists = array();

		$argLists[] = array( array( new PropertyNoValueSnak( $id42 ) ) );
		$argLists[] = array( array( new PropertyNoValueSnak( new PropertyId( 'P9001' ) ) ) );
		$argLists[] = array( array( new PropertyValueSnak( $id42, new StringValue( 'a' ) ) ) );

		return $argLists;
	}

	public function constructorProvider() {
		$id42 = new PropertyId( 'P42' );
		$id9001 = new PropertyId( 'P9001' );

		return array(
			array(),
			array( array() ),
			array( array(
				new PropertyNoValueSnak( $id42 )
			) ),
			array( array(
				new PropertyNoValueSnak( $id42 ),
				new PropertyNoValueSnak( $id9001 ),
			) ),
			array( array(
				new PropertyNoValueSnak( $id42 ),
				new PropertyNoValueSnak( $id9001 ),
				new PropertyValueSnak( $id42, new StringValue( 'a' ) ),
			) ),
		);
	}

	/**
	 * @dataProvider instanceProvider
	 *
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
	 *
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
	 *
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

	/**
	 * @dataProvider instanceProvider
	 * 
	 * @param SnakList $snaks
	 */
	public function testToArrayRoundtrip( SnakList $snaks ) {
		$serialization = serialize( $snaks->toArray() );
		$array = $snaks->toArray();

		$this->assertInternalType( 'array', $array, 'toArray should return array' );

		foreach ( array( $array, unserialize( $serialization ) ) as $data ) {
			$copy = SnakList::newFromArray( $data );

			$this->assertInstanceOf( '\Wikibase\Snaks', $copy, 'newFromArray should return object implementing Snaks' );

			$this->assertTrue( $snaks->equals( $copy ), 'getArray newFromArray roundtrip should work' );
		}
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
		$rawArguments = array(
			//default order
			array( array(), array() ),
			array(
				array( new PropertyNoValueSnak( $id1 ) ),
				array( new PropertyNoValueSnak( $id1 ) ),
			),
			array(
				array(
					new PropertyNoValueSnak( $id2 ),
					new PropertyNoValueSnak( $id1 ),
				),
				array(
					new PropertyNoValueSnak( $id2 ),
					new PropertyNoValueSnak( $id1 ),
				),
			),
			array(
				array(
					new PropertyNoValueSnak( $id1 ),
					new PropertyNoValueSnak( $id2 ),
					new PropertyValueSnak( $id1, new StringValue( 'a' ) ),
				),
				array(
					new PropertyNoValueSnak( $id1 ),
					new PropertyValueSnak( $id1, new StringValue( 'a' ) ),
					new PropertyNoValueSnak( $id2 ),
				),
			),
			//with additional order
			array(
				array(
					new PropertyNoValueSnak( $id3 ),
					new PropertyNoValueSnak( $id2 ),
					new PropertyValueSnak( $id1, new StringValue( 'a' ) ),
				),
				array(
					new PropertyNoValueSnak( $id2 ),
					new PropertyNoValueSnak( $id3 ),
					new PropertyValueSnak( $id1, new StringValue( 'a' ) ),
				),
				array( $id2->getSerialization() )
			),
			array(
				array(
					new PropertyNoValueSnak( $id3 ),
					new PropertyNoValueSnak( $id2 ),
					new PropertyNoValueSnak( $id2 ),
					new PropertyValueSnak( $id1, new StringValue( 'a' ) ),
					new PropertyNoValueSnak( $id1 ),
				),
				array(

					new PropertyValueSnak( $id1, new StringValue( 'a' ) ),
					new PropertyNoValueSnak( $id1 ),
					new PropertyNoValueSnak( $id3 ),
					new PropertyNoValueSnak( $id2 ),
					new PropertyNoValueSnak( $id2 ),
				),
				array( $id1->getSerialization() )
			),
		);

		$arguments = array();

		foreach( $rawArguments as $rawArgument ) {
			if( !array_key_exists( 2, $rawArgument ) ){
				$rawArgument[2] = array();
			}
			$arguments[] = array( new $class( $rawArgument[0] ), new $class( $rawArgument[1] ), $rawArgument[2] );
		}

		return $arguments;
	}

	/**
	 * @dataProvider orderByPropertyProvider
	 *
	 * @param SnakList $snakList
	 * @param SnakList $expected
	 * @param array $order
	 */
	public function testOrderByProperty( SnakList $snakList, SnakList $expected, $order = array() ) {
		$initialSnakList = SnakList::newFromArray( $snakList->toArray() );

		$snakList->orderByProperty( $order );

		// Instantiate new SnakList resetting the snaks' array keys. This allows comparing the
		// reordered SnakList to the expected SnakList.
		$orderedSnakList = SnakList::newFromArray( $snakList->toArray() );

		$this->assertEquals( $expected, $orderedSnakList );

		if( $orderedSnakList->equals( $initialSnakList ) ) {
			$this->assertTrue( $initialSnakList->getHash() === $snakList->getHash() );
		} else {
			$this->assertFalse( $initialSnakList->getHash() === $snakList->getHash() );
		}
	}

}

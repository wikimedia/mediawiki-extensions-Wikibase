<?php

namespace Wikibase\DataModel\Tests\Snak;

use DataValues\StringValue;
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
	 * @dataProvider invalidConstructorArgumentsProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenInvalidConstructorArguments_constructorThrowsException( $input ) {
		new SnakList( $input );
	}

	public function invalidConstructorArgumentsProvider() {
		$id1 = new PropertyId( 'P1' );

		return array(
			array( false ),
			array( 1 ),
			array( 0.1 ),
			array( 'string' ),
			array( $id1 ),
			array( new PropertyNoValueSnak( $id1 ) ),
			array( new PropertyValueSnak( $id1, new StringValue( 'a' ) ) ),
			array( array( null ) ),
			array( array( $id1 ) ),
			array( array( new SnakList() ) ),
		);
	}

	public function testGivenAssociativeArray_constructorPreservesArrayKeys() {
		$snakList = new SnakList( array( 'key' => new PropertyNoValueSnak( 1 ) ) );
		$this->assertSame( array( 'key' ), array_keys( iterator_to_array( $snakList ) ) );
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
		$rawArguments = array(
			'Default order' => array(
				array(),
				array(),
			),
			'Unknown id in order' => array(
				array(),
				array(),
				array( 'P1' )
			),
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
			'With additional order' => array(
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
				array( 'P2' )
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
				array( 'P1' )
			),
		);

		$arguments = array();

		foreach ( $rawArguments as $key => $rawArgument ) {
			$arguments[$key] = array(
				new $class( $rawArgument[0] ),
				new $class( $rawArgument[1] ),
				array_key_exists( 2, $rawArgument ) ? $rawArgument[2] : array()
			);
		}

		return $arguments;
	}

	/**
	 * @dataProvider orderByPropertyProvider
	 */
	public function testOrderByProperty( SnakList $snakList, SnakList $expected, array $order = array() ) {
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

}

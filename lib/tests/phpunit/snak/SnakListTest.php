<?php

namespace Wikibase\Test;
use Wikibase\SnakList as SnakList;
use Wikibase\Snaks as Snaks;
use Wikibase\Snak as Snak;
use \Wikibase\PropertyValueSnak as PropertyValueSnak;
use \Wikibase\InstanceOfSnak as InstanceOfSnak;
use \DataValue\DataValueObject as DataValueObject;
use \Wikibase\Hashable as Hashable;

/**
 * Tests for the Wikibase\SnakList class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
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
		return array(
			new InstanceOfSnak( 42 ),
			new InstanceOfSnak( 9001 ),
			new PropertyValueSnak( 42, new DataValueObject() ),
		);
	}

	public function constructorProvider() {
		return array(
			array(),
			array( array() ),
			array( array(
				new InstanceOfSnak( 42 )
			) ),
			array( array(
				new InstanceOfSnak( 42 ),
				new InstanceOfSnak( 9001 ),
			) ),
			array( array(
				new InstanceOfSnak( 42 ),
				new InstanceOfSnak( 9001 ),
				new PropertyValueSnak( 42, new DataValueObject() ),
			) ),
		);
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\SnakList $array
	 */
	public function testHasSnak( SnakList $array ) {
		/**
		 * @var Hashable $hashable
		 */
		foreach ( iterator_to_array( $array ) as $hashable ) {
			$this->assertTrue( $array->hasSnak( $hashable ) );
			$this->assertTrue( $array->hasSnakHash( $hashable->getHash() ) );
			$array->removeSnak( $hashable );
			$this->assertFalse( $array->hasSnak( $hashable ) );
			$this->assertFalse( $array->hasSnakHash( $hashable->getHash() ) );
		}
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\SnakList $array
	 */
	public function testRemoveSnak( SnakList $array ) {
		$elementCount = $array->count();

		/**
		 * @var Hashable $element
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

		$element = new \Wikibase\InstanceOfSnak( 42 );

		$array->removeSnak( $element );
		$array->removeSnakHash( $element->getHash() );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\SnakList $array
	 */
	public function testAddSnak( SnakList $array ) {
		$elementCount = $array->count();

		$elements = $this->elementInstancesProvider();
		$element = array_shift( $elements );

		if ( !$array->hasSnak( $element ) ) {
			++$elementCount;
		}

		$this->assertEquals( !$array->hasSnak( $element ), $array->addSnak( $element ) );

		$this->assertEquals( $elementCount, $array->count() );

		$this->assertFalse( $array->addSnak( $element ) );

		$this->assertEquals( $elementCount, $array->count() );
	}

}

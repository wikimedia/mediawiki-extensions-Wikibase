<?php

namespace Wikibase\DataModel\Tests\HashArray;

use Hashable;
use Wikibase\DataModel\Fixtures\HashArrayElement;
use Wikibase\DataModel\HashArray;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;

/**
 * @covers Wikibase\DataModel\HashArray
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group HashArray
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class HashArrayWithoutDuplicatesTest extends HashArrayTest {

	public function constructorProvider() {
		$argLists = [];

		$argLists[] = [ HashArrayElement::getInstances() ];
		$argLists[] = [ array_merge( HashArrayElement::getInstances(), HashArrayElement::getInstances() ) ];

		return $argLists;
	}

	public function getInstanceClass() {
		return 'Wikibase\DataModel\Fixtures\HashArrayWithoutDuplicates';
	}

	public function elementInstancesProvider() {
		return $this->arrayWrap( array_merge(
			$this->arrayWrap( HashArrayElement::getInstances() ),
			[ HashArrayElement::getInstances() ]
		) );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param HashArray $array
	 */
	public function testAddElement( HashArray $array ) {
		$elementCount = $array->count();

		$elements = $this->elementInstancesProvider();
		$element = array_shift( $elements );
		$element = $element[0][0];

		if ( !$array->hasElement( $element ) ) {
			++$elementCount;
		}

		$this->assertEquals(
			!$array->hasElement( $element ),
			$array->addElement( $element ),
			'Adding an element should only work if its not already there'
		);

		$this->assertEquals(
			$elementCount,
			$array->count(),
			'Element count should only increase if the element was not there yet'
		);

		$this->assertFalse(
			$array->addElement( $element ),
			'Adding an already present element should not work'
		);

		$this->assertEquals(
			$elementCount,
			$array->count(),
			'Element count should not increase if the element is already there'
		);
	}

	/**
	 * @dataProvider instanceProvider
	 * @param HashArray $array
	 */
	public function testHasElement( HashArray $array ) {
		/**
		 * @var Hashable $hashable
		 */
		foreach ( iterator_to_array( $array ) as $hashable ) {
			$this->assertTrue( $array->hasElement( $hashable ) );
			$this->assertTrue( $array->hasElementHash( $hashable->getHash() ) );
			$array->removeElement( $hashable );
			$this->assertFalse( $array->hasElement( $hashable ) );
			$this->assertFalse( $array->hasElementHash( $hashable->getHash() ) );
		}

		$this->assertTrue( true );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param HashArray $array
	 */
	public function testRemoveElement( HashArray $array ) {
		$elementCount = $array->count();

		/**
		 * @var Hashable $element
		 */
		foreach ( iterator_to_array( $array ) as $element ) {
			$this->assertTrue( $array->hasElement( $element ) );

			if ( $elementCount % 2 === 0 ) {
				$array->removeElement( $element );
			}
			else {
				$array->removeByElementHash( $element->getHash() );
			}

			$this->assertFalse( $array->hasElement( $element ) );
			$this->assertEquals( --$elementCount, $array->count() );
		}

		$element = new PropertyNoValueSnak( 42 );

		$array->removeElement( $element );
		$array->removeByElementHash( $element->getHash() );

		$this->assertTrue( true );
	}

}

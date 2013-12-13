<?php

namespace Wikibase\Test;

use Wikibase\HashArray;

/**
 * @covers Wikibase\HashArray
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group HashArray
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

class HashArrayWithoutDuplicatesTest extends HashArrayTest {

	public function constructorProvider() {
		$argLists = array();

		$argLists[] = array( HashArrayElement::getInstances() );
		$argLists[] = array( array_merge( HashArrayElement::getInstances(), HashArrayElement::getInstances() ) );

		return $argLists;
	}

	public function getInstanceClass() {
		return '\Wikibase\Test\HashArrayWithoutDuplicates';
	}

	public function elementInstancesProvider() {
		return $this->arrayWrap( array_merge(
			$this->arrayWrap( HashArrayElement::getInstances() ),
			array( HashArrayElement::getInstances() )
		) );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\HashArray $array
	 */
	public function testAddElement( HashArray $array ) {
		$elementCount = $array->count();

		$elements = $this->elementInstancesProvider();
		$element = array_shift( $elements );
		$element = $element[0][0];

		if ( !$array->hasElement( $element ) ) {
			++$elementCount;
		}

		$this->assertEquals( !$array->hasElement( $element ), $array->addElement( $element ), 'Adding an element should only work if its not already there' );

		$this->assertEquals( $elementCount, $array->count(), 'Element count should only increase if the element was not there yet' );

		$this->assertFalse( $array->addElement( $element ), 'Adding an already present element should not work' );

		$this->assertEquals( $elementCount, $array->count(), 'Element count should not increase if the element is already there' );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\HashArray $array
	 */
	public function testRemoveDuplicates( HashArray $array ) {
		$count = count( $array );
		$array->removeDuplicates();

		$this->assertEquals( $count, count( $array ), 'Count should be the same after removeDuplicates since there can be none' );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\HashArray $array
	 */
	public function testGetHash( HashArray $array ) {
		$hash = $array->getHash();

		$this->assertEquals( $hash, $array->getHash() );

		$elements = $this->elementInstancesProvider();
		$element = array_shift( $elements );
		$element = $element[0][0];

		$hasElement = $array->hasElement( $element );
		$array->addElement( $element );

		$this->assertTrue( ( $hash === $array->getHash() ) === $hasElement );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\HashArray $array
	 */
	public function testIndicesAreUpToDate( HashArray $array ) {
		$this->assertInternalType( 'boolean', $array->indicesAreUpToDate() );

		$mutable = new MutableHashable();

		$array->addElement( $mutable );

		$mutable->text = '~[,,_,,]:3';

		$this->assertFalse( $array->indicesAreUpToDate() );

		$array->rebuildIndices();

		$this->assertTrue( $array->indicesAreUpToDate() );
	}

}

class HashArrayWithoutDuplicates extends HashArray {

	public function getObjectType() {
		return '\Hashable';
	}

	public function __construct( $input = null ) {
		$this->acceptDuplicates = false;
		parent::__construct( $input );
	}

}



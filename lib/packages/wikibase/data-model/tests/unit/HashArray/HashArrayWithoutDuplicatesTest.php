<?php

namespace Wikibase\DataModel\Tests\HashArray;

use Wikibase\DataModel\Fixtures\HashArrayElement;
use Wikibase\DataModel\Fixtures\MutableHashable;
use Wikibase\DataModel\HashArray;

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
		$argLists = array();

		$argLists[] = array( HashArrayElement::getInstances() );
		$argLists[] = array( array_merge( HashArrayElement::getInstances(), HashArrayElement::getInstances() ) );

		return $argLists;
	}

	public function getInstanceClass() {
		return 'Wikibase\DataModel\Fixtures\HashArrayWithoutDuplicates';
	}

	public function elementInstancesProvider() {
		return $this->arrayWrap( array_merge(
			$this->arrayWrap( HashArrayElement::getInstances() ),
			array( HashArrayElement::getInstances() )
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
	public function testRemoveDuplicates( HashArray $array ) {
		$count = count( $array );
		$array->removeDuplicates();

		$this->assertCount(
			$count,
			$array,
			'Count should be the same after removeDuplicates since there can be none'
		);
	}

	/**
	 * @dataProvider instanceProvider
	 * @param HashArray $array
	 */
	public function testGetHash( HashArray $array ) {
		$hash = $array->getHash();

		$this->assertSame( $hash, $array->getHash() );

		$elements = $this->elementInstancesProvider();
		$element = array_shift( $elements );
		$element = $element[0][0];

		$array->addElement( $element );

		if ( $array->hasElement( $element ) ) {
			$this->assertSame( $hash, $array->getHash() );
		} else {
			$this->assertNotSame( $hash, $array->getHash() );
		}
	}

	/**
	 * @dataProvider instanceProvider
	 * @param HashArray $array
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

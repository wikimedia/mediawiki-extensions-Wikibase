<?php

namespace Wikibase\DataModel\Tests\HashArray;

use Hashable;
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
class HashArrayWithDuplicatesTest extends HashArrayTest {

	public function constructorProvider() {
		$argLists = array();

		$argLists[] = array( HashArrayElement::getInstances() );
		$argLists[] = array( array_merge( HashArrayElement::getInstances(), HashArrayElement::getInstances() ) );

		return $argLists;
	}

	public function getInstanceClass() {
		return 'Wikibase\DataModel\Fixtures\HashArrayWithDuplicates';
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

		++$elementCount;

		$this->assertTrue( $array->addElement( $element ), 'Adding an element should always work' );

		$this->assertEquals( $elementCount, $array->count(), 'Adding an element should always increase the count' );

		$this->assertTrue( $array->addElement( $element ), 'Adding an element should always work' );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param HashArray $array
	 */
	public function testRemoveDuplicates( HashArray $array ) {
		$count = count( $array );
		$duplicateCount = 0;
		$hashes = array();

		/**
		 * @var Hashable $hashable
		 */
		foreach ( $array as $hashable ) {
			if ( in_array( $hashable->getHash(), $hashes ) ) {
				$duplicateCount++;
			}
			else {
				$hashes[] = $hashable->getHash();
			}
		}

		$array->removeDuplicates();

		$this->assertEquals(
			$count - $duplicateCount,
			count( $array ),
			'Count should decrease by the number of duplicates after removing duplicates'
		);
	}

	/**
	 * @dataProvider instanceProvider
	 * @param HashArray $array
	 */
	public function testGetHash( HashArray $array ) {
		$hash = $array->getHash();

		$this->assertEquals( $hash, $array->getHash() );

		$elements = $this->elementInstancesProvider();
		$element = array_shift( $elements );
		$element = $element[0][0];

		$array->addElement( $element );

		$newHash = $array->getHash();

		$this->assertNotSame( $hash, $newHash, 'Hash should not be the same after adding an element' );

		$array->addElement( $element );

		$this->assertNotSame(
			$newHash,
			$array->getHash(),
			'Hash should not be the same after adding an existing element again'
		);
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

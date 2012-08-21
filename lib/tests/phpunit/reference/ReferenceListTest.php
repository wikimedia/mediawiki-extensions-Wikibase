<?php

namespace Wikibase\Test;
use Wikibase\ReferenceList as ReferenceList;
use Wikibase\References as References;
use Wikibase\Reference as Reference;
use Wikibase\ReferenceObject as ReferenceObject;
use Wikibase\Hashable as Hashable;

/**
 * Tests for the Wikibase\ReferenceList class.
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
class ReferenceListTest extends HashArrayTest {

	/**
	 * @see GenericArrayObjectTest::getInstanceClass
	 */
	public function getInstanceClass() {
		return '\Wikibase\ReferenceList';
	}

	/**
	 * @see GenericArrayObjectTest::elementInstancesProvider
	 */
	public function elementInstancesProvider() {
		return array(
			new ReferenceObject(),
		);
	}

	public function constructorProvider() {
		return array(
			array(),
			array( new \Wikibase\ReferenceObject() ),
		);
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\ReferenceList $array
	 */
	public function testHasReference( ReferenceList $array ) {
		/**
		 * @var Hashable $hashable
		 */
		foreach ( iterator_to_array( $array ) as $hashable ) {
			$this->assertTrue( $array->hasReference( $hashable ) );
			$this->assertTrue( $array->hasReferenceHash( $hashable->getHash() ) );
			$array->removeReference( $hashable );
			$this->assertFalse( $array->hasReference( $hashable ) );
			$this->assertFalse( $array->hasReferenceHash( $hashable->getHash() ) );
		}
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\ReferenceList $array
	 */
	public function testRemoveReference( ReferenceList $array ) {
		$elementCount = $array->count();

		/**
		 * @var Hashable $element
		 */
		foreach ( iterator_to_array( $array ) as $element ) {
			$this->assertTrue( $array->hasReference( $element ) );

			if ( $elementCount % 2 === 0 ) {
				$array->removeReference( $element );
			}
			else {
				$array->removeReferenceHash( $element->getHash() );
			}

			$this->assertFalse( $array->hasReference( $element ) );
			$this->assertEquals( --$elementCount, $array->count() );
		}

		$elements = $this->elementInstancesProvider();
		$element = array_shift( $elements );

		$array->removeReference( $element );
		$array->removeReference( $element );
		$array->removeReferenceHash( $element->getHash() );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\ReferenceList $array
	 */
	public function testAddReference( ReferenceList $array ) {
		$elementCount = $array->count();

		$elements = $this->elementInstancesProvider();
		$element = array_shift( $elements );

		if ( !$array->hasReference( $element ) ) {
			++$elementCount;
		}

		$this->assertEquals( !$array->hasReference( $element ), $array->addReference( $element ) );

		$this->assertEquals( $elementCount, $array->count() );

		$this->assertFalse( $array->addReference( $element ) );

		$this->assertEquals( $elementCount, $array->count() );
	}

}

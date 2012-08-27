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
 * @group WikibaseReference
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ReferenceListTest extends \MediaWikiTestCase {

	/**
	 * @see GenericArrayObjectTest::getInstanceClass
	 */
	public function getInstanceClass() {
		return '\Wikibase\ReferenceList';
	}

	public function instanceProvider() {
		$class = $this->getInstanceClass();

		$instances = array();

		foreach ( $this->constructorProvider() as $args ) {
			$instances[] = array( new $class( array_key_exists( 0, $args ) ? $args[0] : null ) );
		}

		return $instances;
	}

	/**
	 * @see GenericArrayObjectTest::elementInstancesProvider
	 */
	public function elementInstancesProvider() {
		$instances = array();

		$instances[] = new ReferenceObject();

		return $this->arrayWrap( $this->arrayWrap( $instances ) );
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
			$array->removeReference( $hashable );
			$this->assertFalse( $array->hasReference( $hashable ) );
		}
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\ReferenceList $array
	 */
	public function testRemoveReference( ReferenceList $array ) {
		$elementCount = count( $array );

		/**
		 * @var Hashable $element
		 */
		foreach ( iterator_to_array( $array ) as $element ) {
			$this->assertTrue( $array->hasReference( $element ) );

			$array->removeReference( $element );

			$this->assertFalse( $array->hasReference( $element ) );
			$this->assertEquals( --$elementCount, count( $array ) );
		}

		$elements = $this->elementInstancesProvider();
		$element = array_shift( $elements );
		$element = $element[0][0];

		$array->removeReference( $element );
		$array->removeReference( $element );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\ReferenceList $array
	 */
	public function testAddReference( ReferenceList $array ) {
		$elementCount = count( $array );

		$elements = $this->elementInstancesProvider();
		$element = array_shift( $elements );
		$element = $element[0][0];

		if ( !$array->hasReference( $element ) ) {
			++$elementCount;
		}

		$array->addReference( $element );

		$this->assertEquals( $elementCount, count( $array ) );
	}

}

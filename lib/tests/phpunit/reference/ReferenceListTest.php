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

	public function getInstanceClass() {
		return '\Wikibase\ReferenceList';
	}

	public function instanceProvider() {
		$class = $this->getInstanceClass();

		$instances = array();

		foreach ( $this->getConstructorArg() as $arg ) {
			$instances[] = array( new $class( $arg ) );
		}

		return $instances;
	}

	public function getElementInstances() {
		$instances = array();

		$instances[] = new ReferenceObject();

		return $instances;
	}

	public function getConstructorArg() {
		return array(
			null,
			array(),
			$this->getElementInstances(),
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

		$elements = $this->getElementInstances();
		$element = array_shift( $elements );

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

		$elements = $this->getElementInstances();
		$element = array_shift( $elements );

		if ( !$array->hasReference( $element ) ) {
			++$elementCount;
		}

		$array->addReference( $element );

		$this->assertEquals( $elementCount, count( $array ) );
	}

}

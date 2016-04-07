<?php

namespace Wikibase\DataModel\Tests\HashArray;

use Hashable;
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
abstract class HashArrayTest extends \PHPUnit_Framework_TestCase {

	public abstract function constructorProvider();

	/**
	 * Returns the name of the concrete class being tested.
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	abstract public function getInstanceClass();

	public function instanceProvider() {
		$class = $this->getInstanceClass();

		$instances = array();

		foreach ( $this->constructorProvider() as $args ) {
			$instances[] = array( new $class( array_key_exists( 0, $args ) ? $args[0] : null ) );
		}

		return $instances;
	}

	/**
	 * @dataProvider instanceProvider
	 * @param HashArray $array
	 */
	public function testHasElement( HashArray $array ) {
		$array->removeDuplicates();

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
		$array->removeDuplicates();

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

	/**
	 * @dataProvider instanceProvider
	 * @param HashArray $array
	 */
	public function testEquals( HashArray $array ) {
		$this->assertTrue( $array->equals( $array ) );
		$this->assertFalse( $array->equals( 42 ) );
	}

	/**
	 * @param array $elements
	 *
	 * @return array[]
	 */
	protected function arrayWrap( array $elements ) {
		return array_map(
			function ( $element ) {
				return array( $element );
			},
			$elements
		);
	}

}

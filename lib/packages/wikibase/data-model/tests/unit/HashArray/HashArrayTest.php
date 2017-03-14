<?php

namespace Wikibase\DataModel\Tests\HashArray;

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

		$instances = [];

		foreach ( $this->constructorProvider() as $args ) {
			$instances[] = [ new $class( array_key_exists( 0, $args ) ? $args[0] : [] ) ];
		}

		return $instances;
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
				return [ $element ];
			},
			$elements
		);
	}

}

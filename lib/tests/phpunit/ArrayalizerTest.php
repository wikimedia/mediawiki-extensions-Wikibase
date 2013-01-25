<?php

namespace Wikibase\Test;
use Wikibase\Arrayalizer;

/**
 * Tests for the Wikibase\Arrayalizer class.
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ArrayalizerTest extends \MediaWikiTestCase {

	public static function provideArrayalize() {
		$dummy = new ArrayalizerTestDummy( "dummy" );
		$yummy = new ArrayalizerTestYummy( "yummy" );

		$noop = array( '\Wikibase\Test\ArrayalizerTest', 'noop' );
		$dummyToArray = array( '\Wikibase\Test\ArrayalizerTest', 'dummyToArray' );
		$dummyAsArray = self::dummyToArray( $dummy );

		return array(
			array( // #0: null
				$noop, // $callback
				null, // $data
				null // $expected
			),

			array( // #1: array()
				$noop, // $callback
				array(), // $data
				array(), // $expected
			),

			array( // #2: "string"
				$noop, // $callback
				"hello", // $data
				"hello", // $expected
			),

			array( // #3: object()
				$noop, // $callback
				$dummy, // $data
				$dummy, // $expected
			),

			array( // #4: object() with conversion
				$dummyToArray, // $callback
				$dummy, // $data
				$dummyAsArray
			),

			array( // #5: array( ... )
				$dummyToArray, // $callback
				array( 1, array( "hi", "there" ), 3 ), // $data
				array( 1, array( "hi", "there" ), 3 ), // $expected
			),

			array( // #6: array( Dummy() ), without callback
				$noop, // $callback
				array( $dummy ), // $data
				array( $dummy ), // $expected
			),

			array( // #7: array( Dummy() ), with callback
				$dummyToArray, // $callback
				array( $dummy ), // $data
				array( $dummyAsArray ), // $expected
			),

			array( // #8: array( Dummy(), Yummy() )
				$dummyToArray, // $callback
				array( // $data
					"d" => $dummy,
					"y" => $yummy
				),
				array( // $expected
					"d" => $dummyAsArray,
					"y" => $yummy,
				),
			),

			array( // #9: array( array( Dummy() ), array( Yummy() ) )
				$dummyToArray, // $callback
				array( 32 => array( $dummy ), array( 45 => $yummy ) ), // $data
				array( // $expected
					32 => array( $dummyAsArray ),
					array( 45 => $yummy ),
				),
			),
		);
	}

	public static function dummyToArray( $dummy ) {
		if ( !$dummy instanceof ArrayalizerTestDummy ) {
			return $dummy;
		}

		return array(
			'type' => get_class( $dummy ),
			'name' => $dummy->getName(),
		);
	}

	/**
	 * @dataProvider provideArrayalize
	 *
	 * @param       $callback
	 * @param mixed $data
	 * @param mixed $expected
	 */
	public function testArrayalize( $callback, $data, $expected ) {
		Arrayalizer::arrayalize( $callback, $data );

		if ( is_array( $expected ) && is_array( $data ) ) {
			$this->assertSameStructure( $expected, $data );
		} elseif ( is_object( $expected ) && is_object( $data ) ) {
			$this->assertSame( $expected, $data );
		} else {
			$this->assertEquals( $expected, $data );
		}
	}

	public static function noop( $data ) {
		return $data;
	}

	public static function provideObjectify() {
		$dummy = new ArrayalizerTestDummy( "dummy" );

		$noop = array( '\Wikibase\Test\ArrayalizerTest', 'noop' );
		$arrayToDumy = array( '\Wikibase\Test\ArrayalizerTest', 'arrayToDummy' );
		$dummyAsArray = ArrayalizerTest::dummyToArray( $dummy );
		$yummyAsArray = array(
			'type' => 'Wikibase\Test\ArrayalizerTestYummy',
			'name' => 'yummy'
		);

		return array(
			array( // #0: null
				$noop, // $callback
				null, // $data
				null // $expected
			),

			array( // #1: array()
				$noop, // $callback
				array(), // $data
				array(), // $expected
			),

			array( // #2: "string"
				$noop, // $callback
				"hello", // $data
				"hello", // $expected
			),

			array( // #3: object()
				$noop, // $callback
				$dummy, // $data
				$dummy, // $expected
			),

			array( // #4: object() with conversion
				$arrayToDumy, // $callback
				$dummyAsArray, // $data
				$dummy
			),

			array( // #5: array( ... )
				$arrayToDumy, // $callback
				array( 1, array( "hi", "there" ), 3 ), // $data
				array( 1, array( "hi", "there" ), 3 ), // $expected
			),

			array( // #6: array( Dummy() ), without callback
				$noop, // $callback
				array( $dummyAsArray ), // $data
				array( $dummyAsArray ), // $expected
			),

			array( // #7: array( Dummy() ), with callback
				$arrayToDumy, // $callback
				array( $dummyAsArray ), // $data
				array( $dummy ), // $expected
			),

			array( // #8: array( Dummy(), Yummy() )
				$arrayToDumy, // $callback
				array( // $data
					"d" => $dummyAsArray,
					"y" => $yummyAsArray,
				),
				array( // $expected
					"d" => $dummy,
					"y" => $yummyAsArray,
				),
			),

			array( // #9: array( array( Dummy() ), array( Yummy() ) )
				$arrayToDumy, // $callback
				array( 32 => array( $dummyAsArray ), array( 45 => $yummyAsArray ) ), // $data
				array( // $expected
					32 => array( $dummy ),
					array( 45 => $yummyAsArray ),
				),
			),
		);
	}

	/**
	 * Return whether the two arrays have the same recursive structure.
	 *
	 * @param array $expected
	 * @param array $actual
	 *
	 * @return bool
	 */
	public static function isSameStructure( array $expected, array $actual ) {
		if ( count( $expected ) != count( $actual ) ) {
			return false;
		}

		foreach ( $expected as $key => $expectedValue ) {
			if ( !array_key_exists( $key, $actual ) ) {
				return false;
			}

			$actualValue = $actual[ $key ];

			if ( is_object( $expectedValue ) && is_object( $actualValue ) ) {
				if ( $expectedValue != $actualValue ) { // use soft ==
					return false;
				}
			} elseif ( is_array( $expectedValue ) && is_array( $actualValue ) ) {
				if ( !self::isSameStructure( $expectedValue, $actualValue ) ) { // recurse
					return false;
				}
			} else {
				if ( $expectedValue !== $actualValue ) { // use strict ===
					return false;
				}
			}
		}

		return true;
	}

	public static function getNotSameStructureMessage( array $expected, array $actual, $message = null ) {
		$fullMessage = "Not the same structure:"
			. "\nexpected: " . \PHPUnit_Util_Type::export( $expected )
			. "\nactual:   " . \PHPUnit_Util_Type::export( $actual );

		if ( $message !== null ) {
			$fullMessage = "$message\n$fullMessage";
		}

		return $fullMessage;
	}

	/**
	 * Assert that the two arrays have the same recursive structure.
	 *
	 * @param array $expected
	 * @param array $actual
	 * @param string|null $message
	 */
	protected function assertSameStructure( array $expected, array $actual, $message = null ) {
		if ( !self::isSameStructure( $expected, $actual ) ) {
			$this->fail( self::getNotSameStructureMessage( $expected, $actual, $message ) );
		}
	}

	public static function arrayToDummy( $array ) {
		if ( !isset( $array['type'] )
			|| $array['type'] !== 'Wikibase\Test\ArrayalizerTestDummy' ) {
			return $array;
		}
		return new ArrayalizerTestDummy( $array['name'] );
	}

	/**
	 * @dataProvider provideObjectify
	 *
	 * @param mixed $callback
	 * @param mixed $data
	 * @param mixed $expected
	 */
	public function testObjectify( $callback, $data, $expected ) {

		Arrayalizer::objectify( $callback, $data );

		if ( is_array( $expected ) && is_array( $data ) ) {
			$this->assertSameStructure( $expected, $data );
		} elseif ( $expected instanceof ArrayalizerTestDummy
			&& $data instanceof ArrayalizerTestDummy ) {
			$this->assertEquals( $expected->getName(), $data->getName() );
		} else {
			$this->assertEquals( $expected, $data );
		}
	}

	public static function provideCallableToString() {
		return array(
			array( // #0: global function
				'foo',
				'foo'
			),

			array( // #1: static method as string
				'Foo::foo',
				'Foo::foo'
			),

			array( // #2: anonymous function
				function () {},
				'Closure->__invoke'
			),

			array( // #3: class name in array
				array( 'Foo', 'foo' ),
				'Foo::foo'
			),

			array( // #4: Object in array
				array( new \Exception(), 'foo' ),
				'Exception->foo'
			),

			array( // #5: not callable
				false,
				'(false)'
			),
		);
	}

	/**
	 * @dataProvider provideCallableToString
	 */
	public function testCallableToString( $callable, $expected ) {
		$actual = Arrayalizer::callableToString( $callable );
		$this->assertEquals( $actual, $expected );
	}

}

/**
 * Test class that will be converted
 */
class ArrayalizerTestDummy {
	protected $name;

	public function __construct( $name ) {
		$this->name = $name;
	}

	public function getName() {
		return $this->name;
	}
}

/**
 * Test class that will not be converted
 */
class ArrayalizerTestYummy {
	protected $name;

	public function __construct( $name ) {
		$this->name = $name;
	}

	public function getName() {
		return $this->name;
	}
}

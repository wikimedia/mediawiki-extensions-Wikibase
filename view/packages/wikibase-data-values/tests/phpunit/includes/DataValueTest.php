<?php

namespace DataValues\Test;

use DataValues\DataValue;

/**
 * Base for unit tests for DataValue implementing classes.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.1
 *
 * @ingroup DataValueTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class DataValueTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Returns the name of the concrete class tested by this test.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public abstract function getClass();

	/**
	 * First element can be a boolean indication if the successive values are valid,
	 * or a string indicating the type of exception that should be thrown (ie not valid either).
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public abstract function constructorProvider();

	/**
	 * Creates and returns a new instance of the concrete class.
	 *
	 * @since 0.1
	 *
	 * @return mixed
	 */
	public function newInstance() {
		$reflector = new \ReflectionClass( $this->getClass() );
		$args = func_get_args();
		$instance = $reflector->newInstanceArgs( $args );
		return $instance;
	}

	/**
	 * @since 0.1
	 *
	 * @return array [instance, constructor args]
	 */
	public function instanceProvider() {
		$phpFails = array( $this, 'newInstance' );

		return array_filter( array_map(
			function( array $args ) use ( $phpFails ) {
				$isValid = array_shift( $args ) === true;

				if ( $isValid ) {
					return array( call_user_func_array( $phpFails, $args ), $args );
				}
				else {
					return false;
				}
			},
			$this->constructorProvider()
		), 'is_array' );
	}

	/**
	 * @dataProvider constructorProvider
	 *
	 * @since 0.1
	 */
	public function testConstructor() {
		$args = func_get_args();

		$valid = array_shift( $args );
		$pokemons = null;

		try {
			$dataItem = call_user_func_array( array( $this, 'newInstance' ), $args );
			$this->assertInstanceOf( $this->getClass(), $dataItem );
		}
		catch ( \Exception $pokemons ) {
			if ( $valid === true ) {
				throw $pokemons;
			}

			if ( is_string( $valid ) ) {
				$this->assertEquals( $valid, get_class( $pokemons ) );
			}
			else {
				$this->assertFalse( $valid );
			}
		}
	}

	/**
	 * @dataProvider instanceProvider
	 * @param DataValue $value
	 * @param array $arguments
	 */
	public function testImplements( DataValue $value, array $arguments ) {
		$this->assertInstanceOf( '\Immutable', $value );
		$this->assertInstanceOf( '\Hashable', $value );
		$this->assertInstanceOf( '\Comparable', $value );
		$this->assertInstanceOf( '\Serializable', $value );
		$this->assertInstanceOf( '\Copyable', $value );
		$this->assertInstanceOf( '\DataValues\DataValue', $value );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param DataValue $value
	 * @param array $arguments
	 */
	public function testGetType( DataValue $value, array $arguments ) {
		$valueType = $value->getType();
		$this->assertInternalType( 'string', $valueType );
		$this->assertTrue( strlen( $valueType ) > 0 );

		// Check whether using getType statically returns the same as called from an instance:
		$staticValueType = call_user_func( array( $this->getClass(), 'getType' ) );
		$this->assertEquals( $staticValueType, $valueType );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param DataValue $value
	 * @param array $arguments
	 */
	public function testSerialization( DataValue $value, array $arguments ) {
		$serialization = serialize( $value );
		$this->assertInternalType( 'string', $serialization );

		$unserialized = unserialize( $serialization );
		$this->assertInstanceOf( '\DataValues\DataValue', $unserialized );

		$this->assertTrue( $value->equals( $unserialized ) );
		$this->assertEquals( $value, $unserialized );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param DataValue $value
	 * @param array $arguments
	 */
	public function testEquals( DataValue $value, array $arguments ) {
		$this->assertTrue( $value->equals( $value ) );

		foreach ( array( true, false, null, 'foo', 42, array(), 4.2 ) as $otherValue ) {
			$this->assertFalse( $value->equals( $otherValue ) );
		}
	}

	/**
	 * @dataProvider instanceProvider
	 * @param DataValue $value
	 * @param array $arguments
	 */
	public function testGetHash( DataValue $value, array $arguments ) {
		$hash = $value->getHash();

		$this->assertInternalType( 'string', $hash );
		$this->assertEquals( $hash, $value->getHash() );
		$this->assertEquals( $hash, $value->getCopy()->getHash() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param DataValue $value
	 * @param array $arguments
	 */
	public function testGetCopy( DataValue $value, array $arguments ) {
		$copy = $value->getCopy();

		$this->assertInstanceOf( '\DataValues\DataValue', $copy );
		$this->assertTrue( $value->equals( $copy ) );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param DataValue $value
	 * @param array $arguments
	 */
	public function testGetValueSimple( DataValue $value, array $arguments ) {
		$value->getValue();
		$this->assertTrue( true );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param DataValue $value
	 * @param array $arguments
	 */
	public function testGetArrayValueSimple( DataValue $value, array $arguments ) {
		$value->getArrayValue();
		$this->assertTrue( true );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param DataValue $value
	 * @param array $arguments
	 */
	public function testNewFromArray( DataValue $value, array $arguments ) {
		$class = get_class( $value );
		$arrayValue = $value->getArrayValue();

		$newInstance = $class::newFromArray( $arrayValue );

		$this->assertTrue( $value->equals( $newInstance ) );

		$dvFactory = new \DataValues\DataValueFactory();

		foreach ( $GLOBALS['wgDataValues'] as $type => $class ) {
			$dvFactory->registerDataValue( $type, $class );
		}

		if ( $dvFactory->hasDataValue( $value->getType() ) ) {
			$newInstance = $dvFactory->newDataValue( $value->getType(), $arrayValue );

			$this->assertTrue( $value->equals( $newInstance ) );
		}
	}

	/**
	 * @dataProvider instanceProvider
	 * @param DataValue $value
	 * @param array $arguments
	 */
	public function testToArray( DataValue $value, array $arguments ) {
		$array = $value->toArray();

		$this->assertInternalType( 'array', $array );

		$this->assertTrue( array_key_exists( 'type', $array ) );
		$this->assertTrue( array_key_exists( 'value', $array ) );

		$this->assertEquals( $value->getType(), $array['type'] );
		$this->assertEquals( $value->getArrayValue(), $array['value'] );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param DataValue $value
	 * @param array $arguments
	 */
	public function testNewFromArrayFactory( DataValue $value, array $arguments ) {
		$dvFactory = new \DataValues\DataValueFactory();

		foreach ( $GLOBALS['wgDataValues'] as $type => $class ) {
			$dvFactory->registerDataValue( $type, $class );
		}

		$this->assertTrue( $value->equals( $dvFactory->newFromArray( $value->toArray() ) ) );
	}

}

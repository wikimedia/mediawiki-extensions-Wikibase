<?php

namespace Wikibase\Test\Snak;

use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakObject;

/**
 * @covers Wikibase\DataModel\Snak\SnakObject
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseSnak
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class SnakObjectTest extends \PHPUnit_Framework_TestCase {

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

		// TODO: use setExpectedException rather then this clutter
		// TODO: separate valid from invalid cases (different test methods)
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
	 */
	public function testGetHash( Snak $omnomnom ) {
		$hash = $omnomnom->getHash();
		$this->assertInternalType( 'string', $hash );
		$this->assertEquals( $hash, $omnomnom->getHash() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetPropertyId( Snak $omnomnom ) {
		$id = $omnomnom->getPropertyId();
		$this->assertInstanceOf( '\Wikibase\DataModel\Entity\EntityId', $id );
		$this->assertEquals( $id, $omnomnom->getPropertyId() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testNewFromArray( Snak $snak ) {
		$data = $snak->toArray();
		$actual = SnakObject::newFromArray( $data );

		$this->assertEquals( $snak, $actual );
	}

}

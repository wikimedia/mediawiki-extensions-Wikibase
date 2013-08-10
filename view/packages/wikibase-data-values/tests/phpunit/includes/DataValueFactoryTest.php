<?php

namespace DataValues\Test;

use DataValues\DataValueFactory;

/**
 * Tests for the DataValues\DataValueFactory class.
 *
 * @since 0.1
 *
 * @ingroup DataValueTest
 *
 * @group DataValue
 * @group DataValueExtensions
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class DataValuesFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testSingleton() {
		$instance = DataValueFactory::singleton();

		$this->assertInstanceOf( 'DataValues\DataValueFactory', $instance );
		$this->assertTrue( DataValueFactory::singleton() === $instance );

		global $wgDataValues;

		foreach ( $wgDataValues as $dataValueType => $dataValueClass ) {
			$this->assertTrue( $instance->hasDataValue( $dataValueType ) );
		}
	}

	/**
	 * @return DataValueFactory
	 */
	protected function getNewInstance() {;
		$factory = new DataValueFactory();
		$factory->registerDataValue( 'string', '\DataValues\StringValue' );
		$factory->registerDataValue( 'unknown', '\DataValues\UnknownValue' );
		$factory->registerDataValue( 'number', 'DataValues\NumberValue' );
		return $factory;
	}

	public function testRegisterDataValue() {
		$factory = $this->getNewInstance();

		$this->assertEquals( array( 'string', 'unknown', 'number' ), $factory->getDataValues() );

		$factory->registerDataValue( 'number', 'DataValues\StringValue' );

		$this->assertInstanceOf( 'DataValues\StringValue', $factory->newDataValue( 'number', '42' ) );
	}

	public static function provideNewDataValue() {
		return array(
			'string value' =>  array( 'string', 'hello', '\DataValues\StringValue' ),
			'unknown value' => array( 'unknown', array( 2, 3 ), '\DataValues\UnknownValue' ),
			'bad type' =>      array( 'foo', 'hello', null, '\DataValues\IllegalValueException' ),
			'bad value' =>     array( 'string', array( 23 ), null, '\DataValues\IllegalValueException' ),
			'no type' =>       array( null, 'hello', null, '\InvalidArgumentException' ),
		);
	}

	/**
	 * @dataProvider provideNewDataValue
	 */
	public function testNewDataValue( $type, $data, $expectedClass, $expectedException = null ) {
		if ( $expectedException ) {
			$this->setExpectedException( $expectedException );
		}

		$factory = $this->getNewInstance();

		$value = $factory->newDataValue( $type, $data );

		$this->assertInstanceOf( $expectedClass, $value );

		$this->assertEquals( $type, $value->getType() );
		$this->assertEquals( $data, $value->getArrayValue() );
	}

	/**
	 * @dataProvider provideNewDataValue
	 */
	public function testTryNewDataValue( $type, $data, $expectedClass, $expectedException = null ) {
		if ( $expectedException !== '\DataValues\IllegalValueException' ) {
			$this->setExpectedException( $expectedException );
		}

		$factory = $this->getNewInstance();

		$value = $factory->tryNewDataValue( $type, $data );

		if ( $expectedException === '\DataValues\IllegalValueException' ) {
			$this->assertInstanceOf( 'DataValues\UnDeserializableValue', $value );
			$this->assertNotNull( $value->getReason() );
			$this->assertEquals( $type, $value->getTargetType() );
			$this->assertEquals( $data, $value->getArrayValue() );
		} else {
			$this->assertInstanceOf( $expectedClass, $value );
			$this->assertEquals( $type, $value->getType() );
			$this->assertEquals( $data, $value->getArrayValue() );
		}
	}

	public static function provideNewFromArray() {
		return array_merge( self::provideNewDataValue(), array(
			'no type' =>  array( null, null, null, '\DataValues\IllegalValueException' ),
			'empty type' =>  array( '', 'bla', null, '\DataValues\IllegalValueException' ),
			'no value' => array( 'string', null, null, '\DataValues\IllegalValueException' ),
		) );
	}

	/**
	 * @dataProvider provideNewFromArray
	 */
	public function testNewFromArray( $type, $data, $expectedClass, $expectedException = null ) {
		if ( $expectedException ) {
			$this->setExpectedException( $expectedException );
		}

		$factory = $this->getNewInstance();

		$array= array();

		if ( $type !== null ) {
			$array['type'] = $type;
		}

		if ( $data !== null ) {
			$array['value'] = $data;
		}

		$value = $factory->newFromArray( $array );

		$this->assertInstanceOf( $expectedClass, $value );
		$this->assertEquals( $data, $value->getArrayValue() );
		$this->assertEquals( $array, $value->toArray() );
	}

	/**
	 * @dataProvider provideNewFromArray
	 */
	public function testTryNewFromArray( $type, $data, $expectedClass, $expectedException = null ) {
		$factory = $this->getNewInstance();

		$array= array();

		if ( $type !== null ) {
			$array['type'] = $type;
		}

		if ( $data !== null ) {
			$array['value'] = $data;
		}

		$value = $factory->tryNewFromArray( $array );

		if ( $expectedException ) {
			$this->assertInstanceOf( 'DataValues\UnDeserializableValue', $value );
			$this->assertNotNull( $value->getReason() );
			$this->assertEquals( $type, $value->getTargetType() );
			$this->assertEquals( $data, $value->getArrayValue() );
		} else {
			$this->assertInstanceOf( $expectedClass, $value );
			$this->assertEquals( $data, $value->getArrayValue() );
			$this->assertEquals( $array, $value->toArray() );
		}
	}

}
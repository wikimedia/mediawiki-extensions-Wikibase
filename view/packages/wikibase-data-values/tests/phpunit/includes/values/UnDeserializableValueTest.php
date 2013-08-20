<?php

namespace DataValues\Test;

use DataValues\DataValue;
use DataValues\StringValue;
use DataValues\UnDeserializableValue;
use DataValues\UnknownValue;

/**
 * @covers DataValues\UnDeserializableValue
 *
 * @file
 * @since 0.1
 *
 * @ingroup DataValue
 *
 * @group DataValue
 * @group DataValueExtensions
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class UnDeserializableValueTest extends DataValueTest {

	/**
	 * @see DataValueTest::getClass
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getClass() {
		return 'DataValues\UnDeserializableValue';
	}

	/**
	 * @see DataValueTest::constructorProvider
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function constructorProvider() {
		$argLists = array();

		$argLists[] = array( true, null, null, 'No type and no data' );
		$argLists[] = array( true, null, 'string', 'A type but no data' );
		$argLists[] = array( true, array( 'stuff' ), 'string', 'A type and bad data' );

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param UnDeserializableValue $value
	 * @param array $arguments
	 */
	public function testGetValue( UnDeserializableValue $value, array $arguments ) {
		$this->assertEquals( $arguments[0], $value->getValue() );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param UnDeserializableValue $value
	 * @param array $arguments
	 */
	public function testGetArrayValue( UnDeserializableValue $value, array $arguments ) {
		$this->assertEquals( $arguments[0], $value->getArrayValue() );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param UnDeserializableValue $value
	 * @param array $arguments
	 */
	public function testGetTargetType( UnDeserializableValue $value, array $arguments ) {
		$this->assertEquals( $arguments[1], $value->getTargetType() );
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

		$this->assertEquals( $value->getTargetType(), $array['type'] );
		$this->assertEquals( $value->getValue(), $array['value'] );
	}

	/**
	 * Dummy implementation, there's nothing to test here.
	 *
	 * @return array
	 */
	public static function dummyProvider() {
		return array(
			array( new UnknownValue( 'dummy' ), array() )
		);
	}

	/**
	 * @dataProvider dummyProvider
	 * @param DataValue $value
	 * @param array $arguments
	 */
	public function testNewFromArray( DataValue $value, array $arguments ) {
		$this->assertFalse( method_exists( $this->getClass(), 'newFromArray' ) );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param DataValue $value
	 * @param array $arguments
	 */
	public function testNewFromArrayFactory( DataValue $value, array $arguments ) {
		$dvFactory = new \DataValues\DataValueFactory();
		$dvFactory->registerDataValue( StringValue::getType(), '\DataValues\StringValue' );

		$data = $value->toArray();
		$newValue = $dvFactory->tryNewFromArray( $data );

		$this->assertEquals( $value->getType(), $newValue->getType() );
		$this->assertEquals( $value->getValue(), $newValue->getValue() );
		$this->assertEquals( $value->getArrayValue(), $newValue->getArrayValue() );
	}
}

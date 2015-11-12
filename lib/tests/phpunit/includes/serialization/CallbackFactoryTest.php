<?php

namespace Wikibase\Lib\Tests\Serialization;

use PHPUnit_Framework_TestCase;
use Wikibase\Lib\Serialization\CallbackFactory;

/**
 * @covers Wikibase\Lib\Serialization\CallbackFactory
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class CallbackFactoryTest extends PHPUnit_Framework_TestCase {

	private function getPropertyDataTypeLookup() {
		$mock = $this->getMock( '\Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup' );

		$mock->expects( $this->once() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnValue( 'propertyDataType' ) );

		return $mock;
	}

	public function testGetCallbackToIndexTags() {
		$instance = new CallbackFactory();
		$callback = $instance->getCallbackToIndexTags( 'tagName' );
		$this->assertInternalType( 'callable', $callback );

		$array = array();
		$array = $callback( $array );
		$this->assertSame( array( '_element' => 'tagName' ), $array );
	}

	/**
	 * @dataProvider kvpKeyNameProvider
	 */
	public function testGetCallbackToSetArrayType( $kvpKeyName, $expected ) {
		$instance = new CallbackFactory();
		$callback = $instance->getCallbackToSetArrayType( 'default', $kvpKeyName );
		$this->assertInternalType( 'callable', $callback );

		$array = array();
		$array = $callback( $array );
		$this->assertSame( $expected, $array );
	}

	public function kvpKeyNameProvider() {
		return array(
			array( null, array( '_type' => 'default' ) ),
			array( 'kvpKeyName', array( '_type' => 'default', '_kvpkeyname' => 'kvpKeyName' ) ),
		);
	}

	/**
	 * @dataProvider addAsArrayElementProvider
	 */
	public function testGetCallbackToRemoveKeys( $addAsArrayElement, $expected ) {
		$instance = new CallbackFactory();
		$callback = $instance->getCallbackToRemoveKeys( $addAsArrayElement );
		$this->assertInternalType( 'callable', $callback );

		$array = array( 'sourceKey' => array() );
		$array = $callback( $array );
		$this->assertSame( array( $expected ), $array );
	}

	public function addAsArrayElementProvider() {
		return array(
			array( null, array() ),
			array( 'keyHolder', array( 'keyHolder' => 'sourceKey' ) ),
		);
	}

	public function testGetCallbackToAddDataTypeToSnaksGroupedByProperty() {
		$instance = new CallbackFactory();
		$dataTypeLookup = $this->getPropertyDataTypeLookup();
		$callback = $instance->getCallbackToAddDataTypeToSnaksGroupedByProperty( $dataTypeLookup );
		$this->assertInternalType( 'callable', $callback );

		$array = array(
			'P1' => array( array() ),
		);
		$array = $callback( $array );
		$this->assertSame( array(
			'P1' => array( array( 'datatype' => 'propertyDataType' ) ),
		), $array );
	}

	public function testGetCallbackToAddDataTypeToSnak() {
		$instance = new CallbackFactory();
		$dataTypeLookup = $this->getPropertyDataTypeLookup();
		$callback = $instance->getCallbackToAddDataTypeToSnak( $dataTypeLookup );
		$this->assertInternalType( 'callable', $callback );

		$array = array(
			'property' => 'P1',
		);
		$array = $callback( $array );
		$this->assertSame( array(
			'property' => 'P1',
			'datatype' => 'propertyDataType',
		), $array );
	}

}

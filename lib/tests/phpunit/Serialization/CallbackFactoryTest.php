<?php

namespace Wikibase\Lib\Tests\Serialization;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Serialization\CallbackFactory;

/**
 * @covers Wikibase\Lib\Serialization\CallbackFactory
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class CallbackFactoryTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return PropertyDataTypeLookup
	 */
	private function getPropertyDataTypeLookup() {
		$mock = $this->getMock( PropertyDataTypeLookup::class );

		$mock->expects( $this->once() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnValue( 'propertyDataType' ) );

		return $mock;
	}

	public function testGetCallbackToIndexTags() {
		$instance = new CallbackFactory();
		$callback = $instance->getCallbackToIndexTags( 'tagName' );
		$this->assertInternalType( 'callable', $callback );

		$array = [];
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

		$array = [];
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

		$array = array( 'sourceKey' => [] );
		$array = $callback( $array );
		$this->assertSame( array( $expected ), $array );
	}

	public function addAsArrayElementProvider() {
		return array(
			array( null, [] ),
			array( 'keyHolder', array( 'keyHolder' => 'sourceKey' ) ),
		);
	}

	public function testGetCallbackToAddDataTypeToSnaksGroupedByProperty() {
		$instance = new CallbackFactory();
		$dataTypeLookup = $this->getPropertyDataTypeLookup();
		$callback = $instance->getCallbackToAddDataTypeToSnaksGroupedByProperty( $dataTypeLookup );
		$this->assertInternalType( 'callable', $callback );

		$array = array(
			'P1' => array( [] ),
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

<?php

namespace Wikibase\Lib\Tests\Serialization;

use PHPUnit4And6Compat;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Serialization\CallbackFactory;

/**
 * @covers Wikibase\Lib\Serialization\CallbackFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class CallbackFactoryTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

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
		$this->assertSame( [ '_element' => 'tagName' ], $array );
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
		return [
			[ null, [ '_type' => 'default' ] ],
			[ 'kvpKeyName', [ '_type' => 'default', '_kvpkeyname' => 'kvpKeyName' ] ],
		];
	}

	public function testGetCallbackToAddDataTypeToSnaksGroupedByProperty() {
		$instance = new CallbackFactory();
		$dataTypeLookup = $this->getPropertyDataTypeLookup();
		$callback = $instance->getCallbackToAddDataTypeToSnaksGroupedByProperty( $dataTypeLookup );
		$this->assertInternalType( 'callable', $callback );

		$array = [
			'P1' => [ [] ],
		];
		$array = $callback( $array );
		$this->assertSame( [
			'P1' => [ [ 'datatype' => 'propertyDataType' ] ],
		], $array );
	}

	public function testGetCallbackToAddDataTypeToSnak() {
		$instance = new CallbackFactory();
		$dataTypeLookup = $this->getPropertyDataTypeLookup();
		$callback = $instance->getCallbackToAddDataTypeToSnak( $dataTypeLookup );
		$this->assertInternalType( 'callable', $callback );

		$array = [
			'property' => 'P1',
		];
		$array = $callback( $array );
		$this->assertSame( [
			'property' => 'P1',
			'datatype' => 'propertyDataType',
		], $array );
	}

}

<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests\Serialization;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Serialization\CallbackFactory;

/**
 * @covers \Wikibase\Lib\Serialization\CallbackFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class CallbackFactoryTest extends \PHPUnit\Framework\TestCase {

	private function getPropertyDataTypeLookup(): PropertyDataTypeLookup {
		$mock = $this->createMock( PropertyDataTypeLookup::class );

		$mock->expects( $this->once() )
			->method( 'getDataTypeIdForProperty' )
			->willReturn( 'propertyDataType' );

		return $mock;
	}

	public function testGetCallbackToIndexTags() {
		$instance = new CallbackFactory();
		$callback = $instance->getCallbackToIndexTags( 'tagName' );
		$this->assertIsCallable( $callback );

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
		$this->assertIsCallable( $callback );

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
		$callback = $instance->getCallbackToAddDataTypeToSnaksGroupedByProperty( $dataTypeLookup, $this->newEntityIdParser() );
		$this->assertIsCallable( $callback );

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
		$callback = $instance->getCallbackToAddDataTypeToSnak( $dataTypeLookup, $this->newEntityIdParser() );
		$this->assertIsCallable( $callback );

		$array = [
			'property' => 'P1',
		];
		$array = $callback( $array );
		$this->assertSame( [
			'property' => 'P1',
			'datatype' => 'propertyDataType',
		], $array );
	}

	private function newEntityIdParser(): EntityIdParser {
		$parser = $this->createMock( EntityIdParser::class );
		$parser->expects( $this->once() )
			->method( 'parse' )
			->willReturnCallback( function ( string $id ) {
				return new NumericPropertyId( $id );
			} );

		return $parser;
	}

}

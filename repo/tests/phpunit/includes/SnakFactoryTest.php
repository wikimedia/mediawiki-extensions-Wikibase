<?php

namespace Wikibase\Repo\Tests;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\DataValueFactory;
use Wikibase\Repo\SnakFactory;

/**
 * @covers \Wikibase\Repo\SnakFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class SnakFactoryTest extends \PHPUnit\Framework\TestCase {

	public function newInstance() {
		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeFactory = new DataTypeFactory( [ 'string' => 'string' ] );
		$dataValueFactory = new DataValueFactory( new DataValueDeserializer( [
			'string' => StringValue::class,
		] ) );

		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( 'p1' ), 'string' );

		$service = new SnakFactory(
			$dataTypeLookup,
			$dataTypeFactory,
			$dataValueFactory
		);

		return $service;
	}

	/**
	 * @dataProvider newSnakProvider
	 */
	public function testNewSnak(
		$propertyId,
		$snakType,
		$rawValue,
		$expectedSnakClass,
		$expectedException = null
	) {
		if ( $expectedException !== null ) {
			$this->expectException( $expectedException );
		}

		$service = $this->newInstance();

		$snak = $service->newSnak( new NumericPropertyId( $propertyId ), $snakType, $rawValue );

		$this->assertInstanceOf( $expectedSnakClass, $snak );
	}

	public function newSnakProvider() {
		return [
			'novalue' => [
				'P1', 'novalue', null,
				PropertyNoValueSnak::class,
			],
			'somevalue' => [
				'P1', 'somevalue', null,
				PropertySomeValueSnak::class,
			],
			'value' => [
				'P1', 'value', '"hello"',
				PropertyValueSnak::class,
			],
			'novalue/badprop' => [
				'P66', 'novalue', null,
				PropertyNoValueSnak::class,
				PropertyDataTypeLookupException::class,
			],
			'somevalue/badprop' => [
				'P66', 'somevalue', null,
				PropertySomeValueSnak::class,
				PropertyDataTypeLookupException::class,
			],
			'value/badprop' => [
				'P66', 'value', '"hello"',
				PropertyValueSnak::class,
				PropertyDataTypeLookupException::class,
			],
			'value/badvalue' => [
				'P1', 'value', [ 'foo' ],
				PropertyValueSnak::class,
				InvalidArgumentException::class,
			],
		];
	}

}

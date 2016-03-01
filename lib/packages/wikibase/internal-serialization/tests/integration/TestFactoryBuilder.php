<?php

namespace Tests\Integration\Wikibase\InternalSerialization;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\InternalSerialization\DeserializerFactory;
use Wikibase\InternalSerialization\LegacyDeserializerFactory;
use Wikibase\InternalSerialization\SerializerFactory;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TestFactoryBuilder {

	public static function newLegacyDeserializerFactory( PHPUnit_Framework_TestCase $testCase ) {
		return new LegacyDeserializerFactory(
			self::newFakeDataValueDeserializer( $testCase ),
			new BasicEntityIdParser()
		);
	}

	private static function newFakeDataValueDeserializer( PHPUnit_Framework_TestCase $testCase ) {
		$dataValueDeserializer = $testCase->getMock( 'Deserializers\Deserializer' );

		$dataValueDeserializer->expects( $testCase->any() )
			->method( 'deserialize' )
			->with( $testCase->equalTo( array( 'type' => 'string', 'value' => 'foo' ) ) )
			->will( $testCase->returnValue( new StringValue( 'foo' ) ) );

		return $dataValueDeserializer;
	}

	public static function newDeserializerFactory( PHPUnit_Framework_TestCase $testCase ) {
		return new DeserializerFactory(
			self::newFakeDataValueDeserializer( $testCase ),
			new BasicEntityIdParser()
		);
	}

	public static function newLegacyDeserializerFactoryWithDataValueSupport() {
		return new LegacyDeserializerFactory(
			self::newRealDataValueDeserializer(),
			new BasicEntityIdParser()
		);
	}

	public static function newDeserializerFactoryWithDataValueSupport() {
		return new DeserializerFactory(
			self::newRealDataValueDeserializer(),
			new BasicEntityIdParser()
		);
	}

	private static function newRealDataValueDeserializer() {
		$dataValueClasses = array(
			'boolean' => 'DataValues\BooleanValue',
			'number' => 'DataValues\NumberValue',
			'string' => 'DataValues\StringValue',
			'unknown' => 'DataValues\UnknownValue',
			'globecoordinate' => 'DataValues\Geo\Values\GlobeCoordinateValue',
			'monolingualtext' => 'DataValues\MonolingualTextValue',
			'multilingualtext' => 'DataValues\MultilingualTextValue',
			'quantity' => 'DataValues\QuantityValue',
			'time' => 'DataValues\TimeValue',
			'wikibase-entityid' => 'Wikibase\DataModel\Entity\EntityIdValue',
		);

		return new DataValueDeserializer( $dataValueClasses );
	}

	public static function newSerializerFactory() {
		return new SerializerFactory( new DataValueSerializer() );
	}

	public static function newCurrentDeserializerFactory() {
		return new \Wikibase\DataModel\DeserializerFactory(
			self::newRealDataValueDeserializer(),
			new BasicEntityIdParser()
		);
	}

}

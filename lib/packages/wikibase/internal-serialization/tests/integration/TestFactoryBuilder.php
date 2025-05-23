<?php

namespace Tests\Integration\Wikibase\InternalSerialization;

use DataValues\BooleanValue;
use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\MonolingualTextValue;
use DataValues\MultilingualTextValue;
use DataValues\NumberValue;
use DataValues\QuantityValue;
use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use DataValues\TimeValue;
use DataValues\UnknownValue;
use Deserializers\Deserializer;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\DeserializerFactory as DataModelDeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\InternalSerialization\DeserializerFactory;
use Wikibase\InternalSerialization\LegacyDeserializerFactory;
use Wikibase\InternalSerialization\SerializerFactory;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TestFactoryBuilder {

	public static function newLegacyDeserializerFactory( TestCase $testCase ): LegacyDeserializerFactory {
		return new LegacyDeserializerFactory(
			self::newFakeDataValueDeserializer( $testCase ),
			new BasicEntityIdParser()
		);
	}

	/**
	 * @param TestCase $testCase
	 *
	 * @return Deserializer
	 */
	private static function newFakeDataValueDeserializer( TestCase $testCase ) {
		$dataValueDeserializer = $testCase->getMockBuilder( Deserializer::class )->getMock();

		$dataValueDeserializer->expects( $testCase->any() )
			->method( 'deserialize' )
			->with( $testCase->equalTo( [ 'type' => 'string', 'value' => 'foo' ] ) )
			->will( $testCase->returnValue( new StringValue( 'foo' ) ) );

		return $dataValueDeserializer;
	}

	public static function newDeserializerFactory( TestCase $testCase ): DeserializerFactory {
		return new DeserializerFactory(
			self::newFakeDataValueDeserializer( $testCase ),
			new BasicEntityIdParser(),
			self::newCurrentDeserializerFactory()
		);
	}

	public static function newLegacyDeserializerFactoryWithDataValueSupport(): LegacyDeserializerFactory {
		return new LegacyDeserializerFactory(
			self::newRealDataValueDeserializer(),
			new BasicEntityIdParser()
		);
	}

	public static function newDeserializerFactoryWithDataValueSupport(): DeserializerFactory {
		return new DeserializerFactory(
			self::newRealDataValueDeserializer(),
			new BasicEntityIdParser(),
			self::newCurrentDeserializerFactory()
		);
	}

	private static function newRealDataValueDeserializer(): DataValueDeserializer {
		$dataValueClasses = [
			'boolean' => BooleanValue::class,
			'number' => NumberValue::class,
			'string' => StringValue::class,
			'unknown' => UnknownValue::class,
			'globecoordinate' => GlobeCoordinateValue::class,
			'monolingualtext' => MonolingualTextValue::class,
			'multilingualtext' => MultilingualTextValue::class,
			'quantity' => QuantityValue::class,
			'time' => TimeValue::class,
			'wikibase-entityid' => EntityIdValue::class,
		];

		return new DataValueDeserializer( $dataValueClasses );
	}

	public static function newSerializerFactory(): SerializerFactory {
		return new SerializerFactory( new DataValueSerializer() );
	}

	public static function newCurrentDeserializerFactory(): DataModelDeserializerFactory {
		return new DataModelDeserializerFactory(
			self::newRealDataValueDeserializer(),
			new BasicEntityIdParser(),
			new InMemoryDataTypeLookup(),
			[],
			[]
		);
	}

}

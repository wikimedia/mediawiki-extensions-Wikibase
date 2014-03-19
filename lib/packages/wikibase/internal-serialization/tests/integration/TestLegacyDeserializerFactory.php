<?php

namespace Tests\Integration\Wikibase\InternalSerialization;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\StringValue;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\InternalSerialization\DeserializerFactory;
use Wikibase\InternalSerialization\LegacyDeserializerFactory;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TestLegacyDeserializerFactory {

	/**
	 * @param PHPUnit_Framework_TestCase $testCase
	 * @return DeserializerFactory
	 */
	public static function newInstance( PHPUnit_Framework_TestCase $testCase ) {
		$dataValueDeserializer = $testCase->getMock( 'Deserializers\Deserializer' );

		$dataValueDeserializer->expects( $testCase->any() )
			->method( 'deserialize' )
			->with( $testCase->equalTo( array( 'type' => 'string', 'value' => 'foo' ) ) )
			->will( $testCase->returnValue( new StringValue( 'foo' ) ) );

		return new LegacyDeserializerFactory(
			$dataValueDeserializer,
			new BasicEntityIdParser()
		);
	}

	public static function newInstanceWithDataValueSupport() {
		$dataValueClasses = array_merge(
			$GLOBALS['evilDataValueMap'],
			array(
				'globecoordinate' => 'DataValues\GlobeCoordinateValue',
				'monolingualtext' => 'DataValues\MonolingualTextValue',
				'multilingualtext' => 'DataValues\MultilingualTextValue',
				'quantity' => 'DataValues\QuantityValue',
				'time' => 'DataValues\TimeValue',
				'wikibase-entityid' => 'Wikibase\DataModel\Entity\EntityIdValue',
			)
		);

		return new LegacyDeserializerFactory(
			new DataValueDeserializer( $dataValueClasses ),
			new BasicEntityIdParser()
		);
	}

}
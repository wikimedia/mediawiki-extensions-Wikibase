<?php

namespace Tests\Integration\Wikibase\InternalSerialization;

use DataValues\StringValue;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\InternalSerialization\DeserializerFactory;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TestDeserializerFactory {

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

		return new DeserializerFactory(
			$dataValueDeserializer,
			new BasicEntityIdParser()
		);
	}

}
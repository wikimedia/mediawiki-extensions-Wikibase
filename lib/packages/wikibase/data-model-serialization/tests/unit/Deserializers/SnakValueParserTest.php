<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\StringValue;
use PHPUnit\Framework\TestCase;
use ValueParsers\ValueParser;
use Wikibase\DataModel\Deserializers\SnakValueParser;

/**
 * @covers \Wikibase\DataModel\Deserializers\SnakValueParser
 *
 * @license GPL-2.0-or-later
 */
class SnakValueParserTest extends TestCase {

	public function testValueWithDataTypeSpecificParser(): void {
		$serializedValue = [ 'type' => 'string', 'value' => 'potato' ];
		$expectedValue = new StringValue( 'potato' );

		$dataType = 'some-special-data-type';
		$parserForDataType = $this->createMock( ValueParser::class );
		$parserForDataType->expects( $this->once() )
			->method( 'parse' )
			->with( $serializedValue )
			->willReturn( $expectedValue );

		$dataValueDeserializer = $this->createMock( DataValueDeserializer::class );
		$dataValueDeserializer->expects( $this->never() )->method( $this->anything() );

		$this->assertSame(
			$expectedValue,
			( new SnakValueParser( $dataValueDeserializer, [ "PT:$dataType" => fn() => $parserForDataType ] ) )
				->parse( $dataType, $serializedValue )
		);
	}

	public function testValueWithoutDataTypeSpecificParser(): void {
		$serializedValue = [ 'type' => 'string', 'value' => 'potato' ];
		$expectedValue = new StringValue( 'potato' );

		$dataValueDeserializer = $this->createMock( DataValueDeserializer::class );
		$dataValueDeserializer->expects( $this->once() )
			->method( 'deserialize' )
			->with( $serializedValue )
			->willReturn( $expectedValue );

		$this->assertSame(
			$expectedValue,
			( new SnakValueParser( $dataValueDeserializer, [] ) )
				->parse( 'some-data-type', $serializedValue )
		);
	}

}

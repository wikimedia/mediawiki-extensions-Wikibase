<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use DataValues\DataValue;
use DataValues\Deserializers\DataValueDeserializer;
use DataValues\StringValue;
use Deserializers\Exceptions\DeserializationException;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\SnakValueDeserializer;

/**
 * @covers \Wikibase\DataModel\Deserializers\SnakValueDeserializer
 *
 * @license GPL-2.0-or-later
 */
class SnakValueDeserializerTest extends TestCase {

	/**
	 * @dataProvider valueProvider
	 */
	public function testSuccess( $builder, $serialization, DataValue $expectedValue ): void {
		$this->assertEquals(
			$expectedValue,
			$this->newDeserializer( [ 'PT:test-type' => $builder ] )->deserialize( 'test-type', $serialization )
		);
	}

	public static function valueProvider(): Generator {
		yield 'callable builder' => [
			fn ( $val ) => new StringValue( $val ),
			[ 'type' => 'string', 'value' => 'potato' ],
			new StringValue( 'potato' ),
		];

		yield 'class builder' => [
			StringValue::class,
			[ 'type' => 'string', 'value' => 'kartoffel' ],
			new StringValue( 'kartoffel' ),
		];
	}

	public function testGivenNoDataTypeSpecificBuilder_usesDataValueDeserializer(): void {
		$expectedValue = new StringValue( 'value from fallback deserializer' );
		$dataValueDeserializer = $this->createStub( DataValueDeserializer::class );
		$dataValueDeserializer->method( 'deserialize' )->willReturn( $expectedValue );

		$this->assertSame(
			$expectedValue,
			$this->newDeserializer( [], $dataValueDeserializer )
				->deserialize( 'unknown', [ 'type' => 'string', 'value' => 'some value' ] )
		);
	}

	public function testGivenBuilderThrows_rethrowsAsDeserializationException(): void {
		$deserializer = $this->newDeserializer( [
			'PT:throwy-type' => function () {
				throw new InvalidArgumentException( 'builder unhappy' );
			},
		] );

		$this->expectException( DeserializationException::class );

		$deserializer->deserialize( 'throwy-type', [ 'type' => 'string', 'value' => 'some value' ] );
	}

	public function newDeserializer( array $builders, ?DataValueDeserializer $dataValueDeserializer = null ): SnakValueDeserializer {
		return new SnakValueDeserializer(
			$dataValueDeserializer ?? $this->createStub( DataValueDeserializer::class ),
			$builders
		);
	}

}

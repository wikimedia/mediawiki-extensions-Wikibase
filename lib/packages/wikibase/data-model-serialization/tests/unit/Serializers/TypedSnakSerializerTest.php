<?php

namespace Tests\Wikibase\DataModel\Serializers;

use PHPUnit\Framework\TestCase;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Serializers\TypedSnakSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\TypedSnak;

/**
 * @covers Wikibase\DataModel\Serializers\TypedSnakSerializer
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TypedSnakSerializerTest extends TestCase {

	/**
	 * @var Serializer
	 */
	private $serializer;

	protected function setUp(): void {
		$snakSerializer = $this->createMock( Serializer::class );

		$snakSerializer->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnValue( [
				'foo' => 'bar',
				'baz' => 42,
			] ) );

		$this->serializer = new TypedSnakSerializer( $snakSerializer );
	}

	/**
	 * @dataProvider serializationProvider
	 */
	public function testDataTypeIsAddedToSnakSerialization( TypedSnak $input, array $expected ) {
		$actualSerialization = $this->serializer->serialize( $input );

		$this->assertEquals( $expected, $actualSerialization );
	}

	public function serializationProvider() {
		$argLists = [];

		$mockSnak = $this->createMock( Snak::class );

		$argLists[] = [
			new TypedSnak( $mockSnak, 'string' ),
			[
				'foo' => 'bar',
				'baz' => 42,
				'datatype' => 'string',
			],
		];

		$argLists[] = [
			new TypedSnak( $mockSnak, 'kittens' ),
			[
				'foo' => 'bar',
				'baz' => 42,
				'datatype' => 'kittens',
			],
		];

		return $argLists;
	}

	public function testWithUnsupportedObject() {
		$this->expectException( UnsupportedObjectException::class );
		$this->serializer->serialize( new PropertyNoValueSnak( 42 ) );
	}

}

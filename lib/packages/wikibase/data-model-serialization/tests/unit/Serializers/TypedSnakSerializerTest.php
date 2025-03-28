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
			->willReturn( [
				'foo' => 'bar',
				'baz' => 42,
			] );

		$this->serializer = new TypedSnakSerializer( $snakSerializer );
	}

	/**
	 * @dataProvider serializationProvider
	 */
	public function testDataTypeIsAddedToSnakSerialization( callable $inputFactory, array $expected ) {
		$actualSerialization = $this->serializer->serialize( $inputFactory( $this ) );

		$this->assertEquals( $expected, $actualSerialization );
	}

	public static function serializationProvider() {
		$argLists = [];

		$argLists[] = [
			fn ( self $self ) => new TypedSnak( $self->createMock( Snak::class ), 'string' ),
			[
				'foo' => 'bar',
				'baz' => 42,
				'datatype' => 'string',
			],
		];

		$argLists[] = [
			fn ( self $self ) => new TypedSnak( $self->createMock( Snak::class ), 'kittens' ),
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

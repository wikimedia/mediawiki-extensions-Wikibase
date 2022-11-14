<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Serialization;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\Repo\RestApi\Serialization\InvalidFieldException;
use Wikibase\Repo\RestApi\Serialization\MissingFieldException;
use Wikibase\Repo\RestApi\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Serialization\ReferenceDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Serialization\ReferenceDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ReferenceDeserializerTest extends TestCase {

	/**
	 * @dataProvider serializationProvider
	 */
	public function testDeserialize( Reference $expectedReference, array $serialization ): void {
		$this->assertEquals(
			$expectedReference,
			$this->newDeserializer()->deserialize( $serialization )
		);
	}

	public function serializationProvider(): Generator {
		yield 'empty reference' => [
			new Reference(),
			[ 'parts' => [] ],
		];

		yield 'reference with two parts' => [
			new Reference( [
				new PropertySomeValueSnak( new NumericPropertyId( 'P123' ) ),
				new PropertySomeValueSnak( new NumericPropertyId( 'P321' ) ),
			] ),
			[
				'parts' => [
					[
						'property' => [ 'id' => 'P123' ],
						'value' => [ 'type' => 'somevalue' ],
					],
					[
						'property' => [ 'id' => 'P321' ],
						'value' => [ 'type' => 'somevalue' ],
					],
				],
			],
		];
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testDeserializationErrors( string $expectedException, array $serialization ): void {
		$this->expectException( $expectedException );

		$this->newDeserializer()->deserialize( $serialization );
	}

	public function invalidSerializationProvider(): Generator {
		yield 'missing parts' => [
			MissingFieldException::class,
			[]
		];

		yield 'invalid parts type' => [
			InvalidFieldException::class,
			[ 'parts' => 'potato' ],
		];

		yield 'invalid parts item type' => [
			InvalidFieldException::class,
			[ 'parts' => [ 'potato' ] ],
		];
	}

	private function newDeserializer(): ReferenceDeserializer {
		$propValPairDeserializer = $this->createStub( PropertyValuePairDeserializer::class );
		$propValPairDeserializer->method( 'deserialize' )->willReturnCallback(
			fn( array $p ) => new PropertySomeValueSnak( new NumericPropertyId( $p['property']['id'] ) )
		);

		return new ReferenceDeserializer( $propValPairDeserializer );
	}

}

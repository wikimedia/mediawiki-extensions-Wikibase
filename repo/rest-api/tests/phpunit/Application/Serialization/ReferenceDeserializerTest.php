<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use Throwable;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\MissingFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\ReferenceDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\ReferenceDeserializer
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

	public static function serializationProvider(): Generator {
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
	public function testDeserializationErrors( Exception $expectedException, array $serialization, string $basePath = '' ): void {
		try {
			$this->newDeserializer()->deserialize( $serialization, $basePath );
			$this->fail( 'Expected exception was not thrown.' );
		} catch ( Throwable $e ) {
			$this->assertEquals( $expectedException, $e );
		}
	}

	public static function invalidSerializationProvider(): Generator {
		yield 'missing parts' => [
			new MissingFieldException( 'parts', '' ),
			[],
		];

		yield 'missing parts with path' => [
			new MissingFieldException( 'parts', 'references/0' ),
			[],
			'references/0',
		];

		yield 'null parts' => [
			new InvalidFieldException( 'parts', null, '/parts' ),
			[ 'parts' => null ],
		];

		yield 'invalid parts type' => [
			new InvalidFieldException( 'parts', 'potato', '/parts' ),
			[ 'parts' => 'potato' ],
		];

		yield 'invalid parts item type' => [
			new InvalidFieldException( 'parts', [ 'potato' ], '/parts' ),
			[ 'parts' => [ 'potato' ] ],
		];

		yield 'invalid parts item type with path' => [
			new InvalidFieldException( 'parts', [ 'potato' ], 'references/1/parts' ),
			[ 'parts' => [ 'potato' ] ],
			'references/1',
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

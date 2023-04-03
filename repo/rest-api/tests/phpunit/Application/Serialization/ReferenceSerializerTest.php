<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyValuePairSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\ReferenceSerializer;
use Wikibase\Repo\RestApi\Domain\ReadModel\Property;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyValuePair;
use Wikibase\Repo\RestApi\Domain\ReadModel\Reference;
use Wikibase\Repo\RestApi\Domain\ReadModel\Value;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\ReferenceSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ReferenceSerializerTest extends TestCase {

	/**
	 * @dataProvider serializationProvider
	 */
	public function testSerialize( Reference $reference, array $expectedSerialization ): void {
		$this->assertEquals(
			$expectedSerialization,
			$this->newSerializer()->serialize( $reference )
		);
	}

	public function serializationProvider(): Generator {
		$ref1 = new Reference( 'some-hash-1', [
			new PropertyValuePair(
				new Property( new NumericPropertyId( 'P123' ), 'string' ),
				new Value( Value::TYPE_SOME_VALUE )
			),
		] );
		yield 'reference with one prop value pair' => [
			$ref1,
			[
				'hash' => $ref1->getHash(),
				'parts' => [
					[ 'property' => 'P123 property', 'value' => 'P123 value' ],
				],
			],
		];

		$ref2 = new Reference( 'some-hash-2', [
			new PropertyValuePair(
				new Property( new NumericPropertyId( 'P234' ), 'string' ),
				new Value( Value::TYPE_SOME_VALUE )
			),
			new PropertyValuePair(
				new Property( new NumericPropertyId( 'P345' ), 'string' ),
				new Value( Value::TYPE_SOME_VALUE )
			),
		] );
		yield 'reference with multiple prop value pairs' => [
			$ref2,
			[
				'hash' => $ref2->getHash(),
				'parts' => [
					[ 'property' => 'P234 property', 'value' => 'P234 value' ],
					[ 'property' => 'P345 property', 'value' => 'P345 value' ],
				],
			],
		];
	}

	private function newSerializer(): ReferenceSerializer {
		$propertyValuePairSerializer = $this->createStub( PropertyValuePairSerializer::class );
		$propertyValuePairSerializer->method( 'serialize' )
			->willReturnCallback(
				fn( PropertyValuePair $propertyValuePair ) => [
					'property' => $propertyValuePair->getProperty()->getId() . ' property',
					'value' => $propertyValuePair->getProperty()->getId() . ' value',
				]
			);

		return new ReferenceSerializer( $propertyValuePairSerializer );
	}

}

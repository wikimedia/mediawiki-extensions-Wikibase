<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\Serialization;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\Domains\Crud\Application\Serialization\PropertyValuePairSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\ReferenceSerializer;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\PredicateProperty;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\PropertyValuePair;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Reference;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Value;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\Serialization\ReferenceSerializer
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

	public static function serializationProvider(): Generator {
		$ref1 = new Reference( 'some-hash-1', [
			new PropertyValuePair(
				new PredicateProperty( new NumericPropertyId( 'P123' ), 'string' ),
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
				new PredicateProperty( new NumericPropertyId( 'P234' ), 'string' ),
				new Value( Value::TYPE_SOME_VALUE )
			),
			new PropertyValuePair(
				new PredicateProperty( new NumericPropertyId( 'P345' ), 'string' ),
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

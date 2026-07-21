<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Statements\Application\Serialization;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\Domains\Statements\Application\Serialization\PropertyValuePairSerializer;
use Wikibase\Repo\Domains\Statements\Application\Serialization\ReferenceSerializer;
use Wikibase\Repo\Domains\Statements\Application\Serialization\StatementSerializer;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\PredicateProperty;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\PropertyValuePair;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\Qualifiers;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\Rank;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\Reference;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\References;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\Statement;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\Value;

/**
 * @covers \Wikibase\Repo\Domains\Statements\Application\Serialization\StatementSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementSerializerTest extends TestCase {

	private const STATEMENT_ID = 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';

	/**
	 * @dataProvider serializationProvider
	 */
	public function testSerialize( Statement $statement, array $expectedSerialization ): void {
		$this->assertEquals(
			$expectedSerialization,
			$this->newSerializer()->serialize( $statement )
		);
	}

	public static function serializationProvider(): Generator {
		yield 'no value statement' => [
			self::newStatement( new Value( Value::TYPE_NO_VALUE ), Rank::normal() ),
			[
				'id' => self::STATEMENT_ID,
				'rank' => 'normal',
				'qualifiers' => [],
				'references' => [],
				'property' => 'P123 property',
				'value' => 'P123 value',
			],
		];

		yield 'some value statement with deprecated rank' => [
			self::newStatement( new Value( Value::TYPE_SOME_VALUE ), Rank::deprecated() ),
			[
				'id' => self::STATEMENT_ID,
				'rank' => 'deprecated',
				'qualifiers' => [],
				'references' => [],
				'property' => 'P123 property',
				'value' => 'P123 value',
			],
		];

		yield 'no value statement with qualifiers' => [
			self::newStatement(
				new Value( Value::TYPE_NO_VALUE ),
				Rank::normal(),
				new Qualifiers(
					self::newPropertyValuePair( 'P456' ),
					self::newPropertyValuePair( 'P789' )
				)
			),
			[
				'id' => self::STATEMENT_ID,
				'rank' => 'normal',
				'qualifiers' => [
					[ 'property' => 'P456 property', 'value' => 'P456 value' ],
					[ 'property' => 'P789 property', 'value' => 'P789 value' ],
				],
				'references' => [],
				'property' => 'P123 property',
				'value' => 'P123 value',
			],
		];

		yield 'with references' => [
			self::newStatement(
				new Value( Value::TYPE_NO_VALUE ),
				Rank::normal(),
				new Qualifiers(),
				new References(
					new Reference( 'reference-hash-1', [] ),
					new Reference( 'reference-hash-2', [] )
				)
			),
			[
				'id' => self::STATEMENT_ID,
				'rank' => 'normal',
				'qualifiers' => [],
				'references' => [
					[ 'reference-hash-1' ],
					[ 'reference-hash-2' ],
				],
				'property' => 'P123 property',
				'value' => 'P123 value',
			],
		];
	}

	private static function newStatement(
		Value $value,
		Rank $rank,
		Qualifiers $qualifiers = new Qualifiers(),
		References $references = new References()
	): Statement {
		return new Statement(
			new StatementGuid( new ItemId( 'Q42' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ),
			new PredicateProperty( new NumericPropertyId( 'P123' ), 'string' ),
			$value,
			$rank,
			$qualifiers,
			$references
		);
	}

	private static function newPropertyValuePair( string $propertyId ): PropertyValuePair {
		return new PropertyValuePair(
			new PredicateProperty( new NumericPropertyId( $propertyId ), 'string' ),
			new Value( Value::TYPE_SOME_VALUE )
		);
	}

	private function newSerializer(): StatementSerializer {
		$propertyValuePairSerializer = $this->createStub( PropertyValuePairSerializer::class );
		$propertyValuePairSerializer->method( 'serialize' )
			->willReturnCallback(
				fn( PropertyValuePair $pvp ) => [
					'property' => $pvp->getProperty()->getId() . ' property',
					'value' => $pvp->getProperty()->getId() . ' value',
				]
			);
		$referenceSerializer = $this->createStub( ReferenceSerializer::class );
		$referenceSerializer->method( 'serialize' )
			->willReturnCallback( fn( Reference $ref ) => [ $ref->getHash() ] );

		return new StatementSerializer( $propertyValuePairSerializer, $referenceSerializer );
	}

}

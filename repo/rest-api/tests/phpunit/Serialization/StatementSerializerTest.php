<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Serialization;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Serialization\PropertyValuePairSerializer;
use Wikibase\Repo\RestApi\Serialization\ReferenceSerializer;
use Wikibase\Repo\RestApi\Serialization\StatementSerializer;

/**
 * @covers \Wikibase\Repo\RestApi\Serialization\StatementSerializer
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

	public function serializationProvider(): Generator {
		yield 'no value statement' => [
			NewStatement::noValueFor( 'P123' )
				->withGuid( self::STATEMENT_ID )
				->build(),
			[
				'id' => self::STATEMENT_ID,
				'rank' => 'normal',
				'qualifiers' => [],
				'references' => [],
				'property' => 'P123 property',
				'value' => 'P123 value'
			]
		];

		yield 'some value statement with deprecated rank' => [
			NewStatement::someValueFor( 'P123' )
				->withGuid( self::STATEMENT_ID )
				->withRank( Statement::RANK_DEPRECATED )
				->build(),
			[
				'id' => self::STATEMENT_ID,
				'rank' => 'deprecated',
				'qualifiers' => [],
				'references' => [],
				'property' => 'P123 property',
				'value' => 'P123 value'
			]
		];

		yield 'no value statement with qualifiers' => [
			NewStatement::noValueFor( 'P123' )
				->withGuid( self::STATEMENT_ID )
				->withQualifier( 'P456', 'foo' )
				->withQualifier( 'P789', 'bar' )
				->build(),
			[
				'id' => self::STATEMENT_ID,
				'rank' => 'normal',
				'qualifiers' => [
					[ 'property' => 'P456 property', 'value' => 'P456 value' ],
					[ 'property' => 'P789 property', 'value' => 'P789 value' ],
				],
				'references' => [],
				'property' => 'P123 property',
				'value' => 'P123 value'
			]
		];

		$ref1 = new Reference( [
			new PropertyNoValueSnak( new NumericPropertyId( 'P666' ) ),
			new PropertyNoValueSnak( new NumericPropertyId( 'P777' ) ),
		] );
		$ref2 = new Reference( [
			new PropertyNoValueSnak( new NumericPropertyId( 'P888' ) ),
		] );
		$statementWithReferences = NewStatement::noValueFor( 'P123' )
			->withGuid( self::STATEMENT_ID )
			->build();
		$statementWithReferences->addNewReference( ...$ref1->getSnaks()->getArrayCopy() );
		$statementWithReferences->addNewReference( ...$ref2->getSnaks()->getArrayCopy() );

		yield 'with references' => [
			$statementWithReferences,
			[
				'id' => self::STATEMENT_ID,
				'rank' => 'normal',
				'qualifiers' => [],
				'references' => [
					[ $ref1->getHash() ],
					[ $ref2->getHash() ],
				],
				'property' => 'P123 property',
				'value' => 'P123 value'
			]
		];
	}

	private function newSerializer(): StatementSerializer {
		$propertyValuePairSerializer = $this->createStub( PropertyValuePairSerializer::class );
		$propertyValuePairSerializer->method( 'serialize' )
			->willReturnCallback(
				fn( Snak $snak ) => [
					'property' => $snak->getPropertyId() . ' property',
					'value' => $snak->getPropertyId() . ' value'
				]
			);
		$referenceSerializer = $this->createStub( ReferenceSerializer::class );
		$referenceSerializer->method( 'serialize' )
			->willReturnCallback( fn( Reference $ref ) => [ $ref->getHash() ] );

		return new StatementSerializer( $propertyValuePairSerializer, $referenceSerializer );
	}

}

<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementsDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\StatementsDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementsDeserializerTest extends TestCase {

	/**
	 * @dataProvider provideSerializationAndExpectedOutput
	 */
	public function testDeserialize( array $serialization, StatementList $expectedStatementList ): void {
		$this->assertEquals( $expectedStatementList, $this->newDeserializer()->deserialize( $serialization ) );
	}

	public function provideSerializationAndExpectedOutput(): Generator {
		yield 'deserialize two statements' => [
			[
				'P567' => [ [ 'property' => [ 'id' => 'P567' ], 'value' => [ 'type' => 'somevalue' ] ] ],
				'P789' => [ [ 'property' => [ 'id' => 'P789' ], 'value' => [ 'type' => 'somevalue' ] ] ],
			],
			new StatementList(
				NewStatement::someValueFor( 'P567' )->build(),
				NewStatement::someValueFor( 'P789' )->build()
			),
		];

		yield 'deserialize empty statements' => [ [], new StatementList() ];
	}

	private function newDeserializer(): StatementsDeserializer {
		$propValPairDeserializer = $this->createStub( PropertyValuePairDeserializer::class );
		$propValPairDeserializer->method( 'deserialize' )->willReturnCallback(
			fn( array $p ) => new PropertySomeValueSnak( new NumericPropertyId( $p[ 'property' ][ 'id' ] ) )
		);

		return new StatementsDeserializer(
			new StatementDeserializer( $propValPairDeserializer, $this->createStub( ReferenceDeserializer::class ) )
		);
	}
}

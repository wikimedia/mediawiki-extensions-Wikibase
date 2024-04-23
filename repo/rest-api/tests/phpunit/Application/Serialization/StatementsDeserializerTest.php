<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\Serialization\MissingFieldException;
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

	private StatementDeserializer $statementDeserializer;

	protected function setUp(): void {
		parent::setUp();

		$propValPairDeserializer = $this->createStub( PropertyValuePairDeserializer::class );
		$propValPairDeserializer->method( 'deserialize' )->willReturnCallback(
			fn( array $p ) => new PropertySomeValueSnak( new NumericPropertyId( $p[ 'property' ][ 'id' ] ) )
		);

		$this->statementDeserializer = new StatementDeserializer(
			$propValPairDeserializer,
			$this->createStub( ReferenceDeserializer::class )
		);
	}

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

	public function testGivenInvalidStatementSerialization_throws(): void {
		$expectedException = $this->createStub( MissingFieldException::class );
		$this->statementDeserializer = $this->createMock( StatementDeserializer::class );
		$this->statementDeserializer->expects( $this->once() )
			->method( 'deserialize' )
			->with( $this->anything(), 'P789/0' )
			->willThrowException( $expectedException );

		try {
			$this->newDeserializer()->deserialize( [ 'P789' => [ [ 'property' => [ 'id' => 'P789' ] ] ] ] );
			$this->fail( 'Expected exception was not thrown' );
		} catch ( Exception $e ) {
			$this->assertEquals( $expectedException, $e );
		}
	}

	private function newDeserializer(): StatementsDeserializer {
		return new StatementsDeserializer( $this->statementDeserializer );
	}
}

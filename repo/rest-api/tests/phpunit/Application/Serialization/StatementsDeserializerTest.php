<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldTypeException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidStatementsException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\MissingFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\PropertyIdMismatchException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\SerializationException;
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
			->with( $this->anything(), '/P789/0' )
			->willThrowException( $expectedException );

		try {
			$this->newDeserializer()->deserialize( [ 'P789' => [ [ 'property' => [ 'id' => 'P789' ] ] ] ] );
			$this->fail( 'Expected exception was not thrown' );
		} catch ( MissingFieldException $e ) {
			$this->assertEquals( $expectedException, $e );
		}
	}

	/**
	 * @dataProvider provideInvalidStatementsSerialization
	 * @dataProvider provideStatementGroupPropertyIdMismatch
	 *
	 * @param mixed $serialization
	 */
	public function testGivenInvalidStatementsSerialization_throws( $serialization, SerializationException $expectedException ): void {
		try {
			$this->newDeserializer()->deserialize( $serialization );
			$this->fail( 'Expected exception was not thrown' );
		} catch ( SerializationException $e ) {
			$this->assertEquals( $expectedException, $e );
		}
	}

	public function provideInvalidStatementsSerialization(): Generator {
		$statements = [ [ 'property' => [ 'id' => 'P567' ], 'value' => [ 'type' => 'somevalue' ], 'rank' => 'normal' ] ];
		yield 'statements is sequential array instead of associative array' => [
			$statements,
			new InvalidStatementsException( '', $statements ),
		];

		$statementGroup = [ 'property' => [ 'id' => 'P123' ], 'value' => [ 'type' => 'somevalue' ] ];
		yield 'statement group is associative array instead of sequential array' => [
			[ 'P123' => $statementGroup ],
			new InvalidFieldTypeException( 'P123', '/P123' ),
		];

		yield 'statement not an array' => [
			[ 'P789' => [ 'not a valid statement' ] ],
			new InvalidFieldTypeException( 'P789/0', '/P789/0' ),
		];
	}

	public function provideStatementGroupPropertyIdMismatch(): Generator {
		$propertyIdKey = 'P123';
		$propertyIdValue = 'P789';
		yield 'valid property id key and value' => [
			[ $propertyIdKey => [ [ 'property' => [ 'id' => $propertyIdValue ], 'value' => [ 'type' => 'somevalue' ] ] ] ],
			new PropertyIdMismatchException( $propertyIdKey, $propertyIdValue, "$propertyIdKey/0/property/id" ),
		];

		$validPropertyIdKey = 'P123';
		$invalidPropertyIdValue = [ 'P123' ];
		yield 'invalid property id value type' => [
			[ $validPropertyIdKey => [ [ 'property' => [ 'id' => $invalidPropertyIdValue ], 'value' => [ 'type' => 'somevalue' ] ] ] ],
			new PropertyIdMismatchException( $validPropertyIdKey, $invalidPropertyIdValue, "$validPropertyIdKey/0/property/id" ),
		];

		$invalidPropertyIdKey = '123';
		$validPropertyIdValue = 'P123';
		yield 'invalid property id key' => [
			[ $invalidPropertyIdKey => [ [ 'property' => [ 'id' => $validPropertyIdValue ], 'value' => [ 'type' => 'somevalue' ] ] ] ],
			new PropertyIdMismatchException( $invalidPropertyIdKey, $validPropertyIdValue, "$invalidPropertyIdKey/0/property/id" ),
		];
	}

	private function newDeserializer(): StatementsDeserializer {
		return new StatementsDeserializer( $this->statementDeserializer );
	}
}

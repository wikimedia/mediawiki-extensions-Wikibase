<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Validation;

use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\MissingFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Application\Validation\StatementsValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Validation\StatementsValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementsValidatorTest extends TestCase {

	private PropertyValuePairDeserializer $propValPairDeserializer;

	protected function setUp(): void {
		parent::setUp();

		$this->propValPairDeserializer = $this->createStub( PropertyValuePairDeserializer::class );
		$this->propValPairDeserializer->method( 'deserialize' )
			->willReturnCallback( fn( $p ) => new PropertySomeValueSnak( new NumericPropertyId( $p['property']['id'] ) ) );
	}

	/**
	 * @dataProvider provideValidStatements
	 */
	public function testValid( array $statementsSerialization, StatementList $deserializedStatements ): void {
		$validator = $this->newValidator();

		$this->assertNull( $validator->validate( $statementsSerialization ) );
		$this->assertEquals( $deserializedStatements, $validator->getValidatedStatements() );
	}

	public function provideValidStatements(): Generator {
		yield 'two valid statements' => [
			[
				'P567' => [ $this->newSomeValueSerialization( 'P567' ) ],
				'P789' => [ $this->newSomeValueSerialization( 'P789' ) ],
			],
			new StatementList(
				NewStatement::someValueFor( 'P567' )->build(),
				NewStatement::someValueFor( 'P789' )->build()
			),
		];

		yield 'empty statements array' => [
			[],
			new StatementList(),
		];
	}

	public function testMissingStatementValue_returnsValidationError(): void {
		$this->propValPairDeserializer = $this->createStub( PropertyValuePairDeserializer::class );
		$this->propValPairDeserializer->method( 'deserialize' )->willReturnCallback( function( $_, $basePath ): void {
			throw new MissingFieldException( 'value', $basePath );
		} );

		$predicateId = 'P789';
		$statementWithMissingValue = [ 'property' => [ 'id' => $predicateId ] ];

		$this->assertEquals(
			new ValidationError(
				StatementValidator::CODE_MISSING_FIELD,
				[
					StatementValidator::CONTEXT_PATH => "/$predicateId/0",
					StatementValidator::CONTEXT_FIELD => 'value',
				]
			),
			$this->newValidator()->validate( [ $predicateId => [ $statementWithMissingValue ] ] )
		);
	}

	/**
	 * @dataProvider provideInvalidStatements
	 */
	public function testInvalidStatements_returnsValidationError( array $invalidStatements, ValidationError $expectedError ): void {
		$this->assertEquals( $expectedError, $this->newValidator()->validate( $invalidStatements ) );
	}

	public function provideInvalidStatements(): Generator {
		$invalidStatements = [ 'not a valid statements array' ];
		yield 'statements field is not an associative array' => [
			$invalidStatements,
			new ValidationError(
				StatementsValidator::CODE_STATEMENTS_NOT_ASSOCIATIVE,
				[
					StatementsValidator::CONTEXT_PATH => '',
					StatementsValidator::CONTEXT_STATEMENTS => $invalidStatements,
				]
			),
		];

		$invalidStatementGroup = $this->newSomeValueSerialization( 'P123' );
		yield 'statement group is not a sequential array (list)' => [
			[ 'P123' => $invalidStatementGroup ],
			new ValidationError(
				StatementsValidator::CODE_STATEMENT_GROUP_NOT_SEQUENTIAL,
				[ StatementsValidator::CONTEXT_PATH => '/P123' ]
			),
		];

		$invalidStatement = 'somevalue';
		yield 'statement in statement group is not an array' => [
			[ 'P123' => [ $invalidStatement ] ],
			new ValidationError(
				StatementsValidator::CODE_STATEMENT_NOT_ARRAY,
				[ StatementsValidator::CONTEXT_PATH => '/P123/0' ]
			),
		];

		$invalidStatement = [ 'statement not an associate array' ];
		yield 'statement in statement group is not an associative array' => [
			[ 'P123' => [ $invalidStatement ] ],
			new ValidationError(
				StatementValidator::CODE_INVALID_FIELD_TYPE,
				[ StatementValidator::CONTEXT_FIELD => '/P123/0' ]
			),
		];

		$invalidStatement = [
			'property' => [ 'id' => 'P567' ],
			'value' => [ 'type' => 'somevalue' ],
			'rank' => 'not-a-valid-rank',
		];
		yield 'rank field in a statement is incorrect' => [
			[ 'P567' => [ $invalidStatement ] ],
			new ValidationError(
				StatementValidator::CODE_INVALID_FIELD,
				[
					StatementValidator::CONTEXT_PATH => '/P567/0/rank',
					StatementValidator::CONTEXT_FIELD => 'rank',
					StatementValidator::CONTEXT_VALUE => 'not-a-valid-rank',
				]
			),
		];

		yield 'property id mismatch' => [
			[
				'P123' => [ $this->newSomeValueSerialization( 'P567' ) ],
			],
			new ValidationError(
				StatementsValidator::CODE_PROPERTY_ID_MISMATCH,
				[
					StatementsValidator::CONTEXT_PATH => 'P123/0/property/id',
					StatementsValidator::CONTEXT_PROPERTY_ID_KEY => 'P123',
					StatementsValidator::CONTEXT_PROPERTY_ID_VALUE => 'P567',
				]
			),
		];
	}

	/**
	 * @dataProvider modifiedStatementsProvider
	 */
	public function testValidatesModifiedStatements(
		array $originalSerialization,
		array $serializationToValidate,
		array $expectedValidatedStatements
	): void {
		$actualStatementValidator = $this->newStatementValidator();
		$spyStatementValidator = $this->createMock( StatementValidator::class );
		$spyStatementValidator->expects( $this->exactly( count( $expectedValidatedStatements ) ) )
			->method( 'validate' )
			->with( $this->callback( function( array $statement ) use ( $expectedValidatedStatements, $actualStatementValidator ) {
				$this->assertNull( $actualStatementValidator->validate( $statement ) );

				return in_array( $statement, $expectedValidatedStatements );
			} ) );
		$spyStatementValidator->method( 'getValidatedStatement' )
			->willReturnCallback( fn() => $actualStatementValidator->getValidatedStatement() );

		$statementsValidator = $this->newValidator( $spyStatementValidator );

		$this->assertNull( $statementsValidator->validateModifiedStatements(
			$originalSerialization,
			$this->deserializeStatements( $originalSerialization ),
			$serializationToValidate
		) );
	}

	public function modifiedStatementsProvider(): Generator {
		$existingStatementId = 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		$existingStatement = $this->newSomeValueSerialization( 'P123' );
		$existingStatement['id'] = $existingStatementId;
		$originalSerialization = [ 'P123' => [ $existingStatement ] ];
		yield 'statements unmodified => nothing validated' => [
			$originalSerialization,
			$originalSerialization,
			[],
		];

		$deletedStatement = $this->newSomeValueSerialization( 'P123' );
		$deletedStatement['id'] = 'Q123$DDDDDD-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		yield 'only deleted a statement (group index shift) => nothing validated' => [
			[ 'P123' => [ $deletedStatement, $existingStatement ] ],
			[ 'P123' => [ $existingStatement ] ],
			[],
		];

		$newStatement = $this->newSomeValueSerialization( 'P123' );
		yield 'new statement gets validated' => [
			[],
			[ 'P123' => [ $newStatement ] ],
			[ $newStatement ],
		];

		$unmodifiedStatement = $this->newSomeValueSerialization( 'P321' );
		$unmodifiedStatement['id'] = 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';

		$modifiedStatementId = 'Q123$ZZZZZZ-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		$modifiedStatement = $this->newSomeValueSerialization( 'P123' );
		$modifiedStatement['id'] = $modifiedStatementId;
		yield 'modified statements get validated' => [
			[
				'P123' => [
					[
						'property' => [ 'id' => 'P123' ], 'value' => [ 'type' => 'novalue' ],
						'id' => $modifiedStatementId,
					],
				],
				'P321' => [ $unmodifiedStatement ],
			],
			[
				'P123' => [ $modifiedStatement ],
				'P321' => [ $unmodifiedStatement ],
			],
			[ $modifiedStatement ],
		];
	}

	public function testGivenGetValidatedStatementsCalledBeforeValidate_throws(): void {
		$this->expectException( LogicException::class );
		$this->newValidator()->getValidatedStatements();
	}

	private function newValidator( StatementValidator $statementValidator = null ): StatementsValidator {
		return new StatementsValidator( $statementValidator ?? $this->newStatementValidator() );
	}

	private function newStatementValidator(): StatementValidator {
		return new StatementValidator( $this->newStatementDeserializer() );
	}

	private function newSomeValueSerialization( string $propertyId ): array {
		return [ 'property' => [ 'id' => $propertyId ], 'value' => [ 'type' => 'somevalue' ] ];
	}

	private function deserializeStatements( array $statements ): StatementList {
		return new StatementList( ...array_map(
			[ $this->newStatementDeserializer(), 'deserialize' ],
			array_merge( ...array_values( $statements ) )
		) );
	}

	private function newStatementDeserializer(): StatementDeserializer {
		return new StatementDeserializer(
			$this->propValPairDeserializer,
			$this->createStub( ReferenceDeserializer::class )
		);
	}
}

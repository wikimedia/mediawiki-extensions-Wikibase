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
use Wikibase\Repo\RestApi\Application\Serialization\StatementsDeserializer;
use Wikibase\Repo\RestApi\Application\Validation\ItemStatementsValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Validation\ItemStatementsValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemStatementsValidatorTest extends TestCase {

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
				'P567' => [ [ 'property' => [ 'id' => 'P567' ], 'value' => [ 'type' => 'somevalue' ] ] ],
				'P789' => [ [ 'property' => [ 'id' => 'P789' ], 'value' => [ 'type' => 'somevalue' ] ] ],
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
				ItemStatementsValidator::CODE_MISSING_STATEMENT_DATA,
				[
					ItemStatementsValidator::CONTEXT_PATH => "$predicateId/0",
					ItemStatementsValidator::CONTEXT_FIELD => 'value',
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
				ItemStatementsValidator::CODE_STATEMENTS_NOT_ASSOCIATIVE,
				[
					ItemStatementsValidator::CONTEXT_PATH => '',
					ItemStatementsValidator::CONTEXT_STATEMENTS => $invalidStatements,
				]
			),
		];

		$invalidStatementGroup = [
			'property' => [ 'id' => 'P123' ],
			'value' => [ 'type' => 'somevalue' ],
		];
		yield 'statement group is not a sequential array (list)' => [
			[ 'P123' => $invalidStatementGroup ],
			new ValidationError(
				ItemStatementsValidator::CODE_STATEMENT_GROUP_NOT_SEQUENTIAL,
				[ ItemStatementsValidator::CONTEXT_PATH => 'P123' ]
			),
		];

		$invalidStatement = 'somevalue';
		yield 'statement in statement group is not an array' => [
			[ 'P123' => [ $invalidStatement ] ],
			new ValidationError(
				ItemStatementsValidator::CODE_STATEMENT_NOT_ARRAY,
				[ ItemStatementsValidator::CONTEXT_PATH => 'P123/0' ]
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
				ItemStatementsValidator::CODE_INVALID_STATEMENT_DATA,
				[
					ItemStatementsValidator::CONTEXT_PATH => 'P567/0/rank',
					ItemStatementsValidator::CONTEXT_FIELD => 'rank',
					ItemStatementsValidator::CONTEXT_VALUE => 'not-a-valid-rank',
				]
			),
		];
	}

	public function testGivenGetValidatedStatementsCalledBeforeValidate_throws(): void {
		$this->expectException( LogicException::class );
		$this->newValidator()->getValidatedStatements();
	}

	private function newValidator(): ItemStatementsValidator {
		return new ItemStatementsValidator(
			new StatementsDeserializer(
				new StatementDeserializer(
					$this->propValPairDeserializer,
					$this->createStub( ReferenceDeserializer::class )
				)
			)
		);
	}

}

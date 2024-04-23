<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Validation;

use Generator;
use LogicException;
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
use Wikibase\Repo\RestApi\Application\Validation\ItemStatementsValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Validation\ItemAliasesValidator
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

	public function testInvalidStatements_returnsValidationError(): void {
		$invalidStatements = [ 'not a valid statements array' ];

		$this->assertEquals(
			new ValidationError(
				ItemStatementsValidator::CODE_INVALID_STATEMENTS,
				[ ItemStatementsValidator::CONTEXT_STATEMENTS => $invalidStatements ]
			),
			$this->newValidator()->validate( $invalidStatements )
		);
	}

	public function testInvalidStatementRank_returnsValidationError(): void {
		$predicateId = 'P567';
		$invalidStatement = [
			'property' => [ 'id' => $predicateId ],
			'value' => [ 'type' => 'somevalue' ],
			'rank' => 'not-a-valid-rank',
		];

		$this->assertEquals(
			new ValidationError(
				ItemStatementsValidator::CODE_INVALID_STATEMENT_DATA,
				[
					ItemStatementsValidator::CONTEXT_PATH => "$predicateId/0/rank",
					ItemStatementsValidator::CONTEXT_FIELD => 'rank',
					ItemStatementsValidator::CONTEXT_VALUE => 'not-a-valid-rank',
				]
			),
			$this->newValidator()->validate( [ $predicateId => [ $invalidStatement ] ] )
		);
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

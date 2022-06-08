<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\DataAccess;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueValidators\Result;
use Wikibase\DataModel\Deserializers\StatementDeserializer;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\DataAccess\SnakValidatorStatementValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;
use Wikibase\Repo\Validators\SnakValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\DataAccess\SnakValidatorStatementValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SnakValidatorStatementValidatorTest extends TestCase {

	/**
	 * @var MockObject|StatementDeserializer
	 */
	private $deserializer;

	/**
	 * @var MockObject|SnakValidator
	 */
	private $snakValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->deserializer = WikibaseRepo::getBaseDataModelDeserializerFactory()->newStatementDeserializer();
		$this->snakValidator = $this->createStub( SnakValidator::class );
		$this->snakValidator->method( 'validateStatementSnaks' )->willReturn( Result::newSuccess() );
	}

	public function testGivenInvalidStatementSerialization_validateReturnsValidationError(): void {
		$source = 'statement';
		$error = $this->newValidator()->validate( [ 'invalid' => 'serialization' ], $source );

		$this->assertInstanceOf( ValidationError::class, $error );
		$this->assertSame( $error->getSource(), $source );
	}

	public function testGetValidatedStatement(): void {
		$validator = $this->newValidator();
		$result = $validator->validate(
			[
				'mainsnak' => [
					'snaktype' => 'novalue',
					'property' => 'P123',
				],
				'type' => 'statement'
			],
			'statement'
		);

		$this->assertNull( $result );
		$this->assertInstanceOf( Statement::class, $validator->getValidatedStatement() );
	}

	public function testGivenSyntacticallyValidSerializationButInvalidValueType_validateReturnsValidationError(): void {
		// The data type <-> value type mismatch isn't really tested here since we don't need to test SnakValidator internals.
		// This sort of error happens if e.g. P321 is a string Property, but we're giving it an Item ID as a value here.
		$serialization = [
			'type' => 'statement',
			'mainsnak' => [
				'snaktype' => 'value',
				'property' => 'P321',
				'datavalue' => [
					'type' => 'wikibase-entityid',
					'value' => [ 'id' => 'Q123' ]
				],
			],
		];
		$source = 'statement';

		$deserializedStatement = $this->createStub( Statement::class );

		$this->deserializer = $this->createStub( StatementDeserializer::class );
		$this->deserializer->method( 'deserialize' )->willReturn( $deserializedStatement );

		$this->snakValidator = $this->createMock( SnakValidator::class );
		$this->snakValidator->expects( $this->once() )
			->method( 'validateStatementSnaks' )
			->with( $deserializedStatement )
			->willReturn( Result::newError( [] ) );

		$validator = $this->newValidator();

		$error = $validator->validate( $serialization, $source );

		$this->assertInstanceOf( ValidationError::class, $error );
		$this->assertSame( $source, $error->getSource() );
	}

	private function newValidator(): SnakValidatorStatementValidator {
		return new SnakValidatorStatementValidator(
			$this->deserializer,
			$this->snakValidator
		);
	}

}

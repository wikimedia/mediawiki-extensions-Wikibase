<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\DataAccess;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueValidators\Result;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\DataAccess\SnakValidatorStatementValidator;
use Wikibase\Repo\RestApi\Serialization\SerializationException;
use Wikibase\Repo\RestApi\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;
use Wikibase\Repo\Validators\SnakValidator;

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

		$this->deserializer = $this->createStub( StatementDeserializer::class );
		$this->snakValidator = $this->createStub( SnakValidator::class );
		$this->snakValidator->method( 'validateStatementSnaks' )->willReturn( Result::newSuccess() );
	}

	public function testGivenInvalidStatementSerialization_validateReturnsValidationError(): void {
		$this->deserializer->method( 'deserialize' )->willThrowException( new SerializationException() );

		$error = $this->newValidator()->validate( [ 'invalid' => 'serialization' ] );

		$this->assertInstanceOf( ValidationError::class, $error );
		$this->assertSame( StatementValidator::CODE_INVALID, $error->getCode() );
	}

	public function testGetValidatedStatement(): void {
		$deserializedStatement = $this->createStub( Statement::class );
		$this->deserializer->method( 'deserialize' )->willReturn( $deserializedStatement );

		$validator = $this->newValidator();
		$result = $validator->validate( [
			'property' => [ 'id' => 'P123' ],
			'value' => [ 'type' => 'novalue' ]
		] );

		$this->assertNull( $result );
		$this->assertSame( $deserializedStatement, $validator->getValidatedStatement() );
	}

	public function testGivenSyntacticallyValidSerializationButInvalidValueType_validateReturnsValidationError(): void {
		// The data type <-> value type mismatch isn't really tested here since we don't need to test SnakValidator internals.
		// This sort of error happens if e.g. P321 is a string Property, but we're giving it an Item ID as a value here.
		$serialization = [
			'property' => [ 'id' => 'P321' ],
			'value' => [
				'type' => 'value',
				'content' => [ 'id' => 'Q123' ]
			]
		];

		$deserializedStatement = $this->createStub( Statement::class );

		$this->deserializer = $this->createStub( StatementDeserializer::class );
		$this->deserializer->method( 'deserialize' )->willReturn( $deserializedStatement );

		$this->snakValidator = $this->createMock( SnakValidator::class );
		$this->snakValidator->expects( $this->once() )
			->method( 'validateStatementSnaks' )
			->with( $deserializedStatement )
			->willReturn( Result::newError( [] ) );

		$error = $this->newValidator()->validate( $serialization );

		$this->assertInstanceOf( ValidationError::class, $error );
		$this->assertSame( StatementValidator::CODE_INVALID, $error->getCode() );
	}

	private function newValidator(): SnakValidatorStatementValidator {
		return new SnakValidatorStatementValidator(
			$this->deserializer,
			$this->snakValidator
		);
	}

}

<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\AddPropertyStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatementValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AddPropertyStatementValidatorTest extends TestCase {

	private const ALLOWED_TAGS = [ 'some', 'tags', 'are', 'allowed' ];
	private const COMMENT_LIMIT = 50;
	private StatementValidator $statementValidator;
	private EditMetadataValidator $editMetadataValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->statementValidator = $this->createStub( StatementValidator::class );
		$this->statementValidator->method( 'validate' )->willReturn( null );
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testValidate_withValidRequest(): void {
		$this->newAddPropertyStatementValidator()->assertValidRequest(
			new AddPropertyStatementRequest( 'P42', [ 'valid' => 'statement' ], [], false, null, null )
		);
	}

	public function testGivenInvalidPropertyId_throwsUseCaseError(): void {
		$propertyId = 'X123';
		try {
			$this->newAddPropertyStatementValidator()->assertValidRequest(
				new AddPropertyStatementRequest( $propertyId, [ 'valid' => 'statement' ], [], false, null, null )
			);
			$this->fail( 'Exception not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PROPERTY_ID, $e->getErrorCode() );
			$this->assertSame( 'Not a valid property ID: X123', $e->getErrorMessage() );
		}
	}

	public function testGivenInvalidStatement_throwsUseCaseError(): void {
		$invalidStatement = [ 'this is' => 'not a valid statement' ];
		$expectedError = new ValidationError(
			StatementValidator::CODE_INVALID_FIELD,
			[
				StatementValidator::CONTEXT_FIELD_NAME => 'some-field',
				StatementValidator::CONTEXT_FIELD_VALUE => 'foo',
			]
		);
		$this->statementValidator = $this->createMock( StatementValidator::class );
		$this->statementValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $invalidStatement )
			->willReturn( $expectedError );

		try {
			$this->newAddPropertyStatementValidator()->assertValidRequest(
				new AddPropertyStatementRequest( 'P42', $invalidStatement, [], false, null, null )
			);
			$this->fail( 'Exception not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::STATEMENT_DATA_INVALID_FIELD, $e->getErrorCode() );
			$this->assertSame( "Invalid input for 'some-field'", $e->getErrorMessage() );
			$this->assertSame(
				[ UseCaseError::CONTEXT_PATH => 'some-field', UseCaseError::CONTEXT_VALUE => 'foo' ],
				$e->getErrorContext()
			);
		}
	}

	public function testGivenMissingFieldInStatement_throwsUseCaseError(): void {
		$invalidStatement = [ 'this is' => 'a statement with a missing field' ];
		$expectedError = new ValidationError(
			StatementValidator::CODE_MISSING_FIELD,
			[ StatementValidator::CONTEXT_FIELD_NAME => 'some-field' ]
		);
		$this->statementValidator = $this->createMock( StatementValidator::class );
		$this->statementValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $invalidStatement )
			->willReturn( $expectedError );

		try {
			$this->newAddPropertyStatementValidator()->assertValidRequest(
				new AddPropertyStatementRequest( 'P42', $invalidStatement, [], false, null, null )
			);
			$this->fail( 'Exception not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::STATEMENT_DATA_MISSING_FIELD, $e->getErrorCode() );
			$this->assertSame( 'Mandatory field missing in the statement data: some-field', $e->getErrorMessage() );
			$this->assertSame( [ UseCaseError::CONTEXT_PATH => 'some-field' ], $e->getErrorContext() );
		}
	}

	public function testGivenCommentTooLong_throwsUseCaseError(): void {
		$comment = str_repeat( 'x', self::COMMENT_LIMIT + 1 );
		$expectedError = new ValidationError(
			EditMetadataValidator::CODE_COMMENT_TOO_LONG,
			[ EditMetadataValidator::CONTEXT_COMMENT_MAX_LENGTH => self::COMMENT_LIMIT ]
		);

		try {
			$this->newAddPropertyStatementValidator()->assertValidRequest(
				new AddPropertyStatementRequest( 'P42', [ 'valid' => 'statement' ], [], false, $comment, null )
			);
			$this->fail( 'Exception not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::COMMENT_TOO_LONG, $e->getErrorCode() );
			$this->assertSame(
				'Comment must not be longer than ' . self::COMMENT_LIMIT . ' characters.',
				$e->getErrorMessage()
			);
		}
	}

	public function testGivenInvalidEditTags_throwsUseCaseError(): void {
		$invalidTags = [ 'invalid', 'tag' ];

		try {
			$this->newAddPropertyStatementValidator()->assertValidRequest(
				new AddPropertyStatementRequest( 'P42', [ 'valid' => 'statement' ], $invalidTags, false, null, null )
			);
			$this->fail( 'Exception not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_EDIT_TAG, $e->getErrorCode() );
			$this->assertSame( 'Invalid MediaWiki tag: "invalid"', $e->getErrorMessage() );
		}
	}

	private function newAddPropertyStatementValidator(): AddPropertyStatementValidator {
		return new AddPropertyStatementValidator(
			new PropertyIdValidator(),
			$this->statementValidator,
			new EditMetadataValidator( self::COMMENT_LIMIT, self::ALLOWED_TAGS )
		);
	}

}

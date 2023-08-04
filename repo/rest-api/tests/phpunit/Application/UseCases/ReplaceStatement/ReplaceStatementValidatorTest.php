<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\ReplaceStatement;

use CommentStore;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\RequestedSubjectIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatementValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ReplaceStatementValidatorTest extends TestCase {

	private RequestedSubjectIdValidator $requestedSubjectIdValidator;
	private StatementValidator $statementValidator;

	private const ALLOWED_TAGS = [ 'some', 'tags', 'are', 'allowed' ];

	protected function setUp(): void {
		parent::setUp();

		$this->requestedSubjectIdValidator = $this->createStub( RequestedSubjectIdValidator::class );
		$this->statementValidator = $this->createStub( StatementValidator::class );
		$this->statementValidator->method( 'validate' )->willReturn( null );
	}

	/**
	 * @dataProvider provideValidRequest
	 * @doesNotPerformAssertions
	 */
	public function testValidate_withValidRequest( array $requestData ): void {
		$replaceStatementValidator = $this->newReplaceStatementValidator();
		$replaceStatementValidator->assertValidRequest( $this->newUseCaseRequest( $requestData ) );
		$replaceStatementValidator->getValidatedStatement();
	}

	public static function provideValidRequest(): Generator {
		$itemId = 'Q123';
		$statementId = $itemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		yield '[Item] Valid with requested subject id' => [
			[
				'$subjectId' => $itemId,
				'$statementId' => $statementId,
				'$statement' => [ 'valid' => 'statement' ],
			],
		];
		yield '[Item] Valid without subject ID' => [
			[
				'$statementId' => $statementId,
				'$statement' => [ 'valid' => 'statement' ],
			],
		];

		$propertyId = 'P123';
		$statementId = $propertyId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		yield '[Property] Valid with requested subject id' => [
			[
				'$subjectId' => $propertyId,
				'$statementId' => $statementId,
				'$statement' => [ 'valid' => 'statement' ],
			],
		];
		yield '[Property] Valid without subject ID' => [
			[
				'$statementId' => $statementId,
				'$statement' => [ 'valid' => 'statement' ],
			],
		];
	}

	public function testValidate_withInvalidSubjectId(): void {
		$subjectId = 'X123';

		$this->requestedSubjectIdValidator = $this->createMock( RequestedSubjectIdValidator::class );
		$this->requestedSubjectIdValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $subjectId )
			->willReturn(
				new ValidationError(
					RequestedSubjectIdValidator::CODE_INVALID,
					[ RequestedSubjectIdValidator::CONTEXT_VALUE => $subjectId ]
				)
			);

		try {
			$this->newReplaceStatementValidator()->assertValidRequest(
				$this->newUseCaseRequest( [
					'$subjectId' => $subjectId,
					'$statementId' => 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
					'$statement' => [ 'valid' => 'statement' ],
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_STATEMENT_SUBJECT_ID, $e->getErrorCode() );
			$this->assertSame( $subjectId, $e->getErrorContext()[UseCaseError::CONTEXT_SUBJECT_ID] );
		}
	}

	/**
	 * @dataProvider provideStatementSubjectId
	 */
	public function testValidate_withInvalidStatementId( string $subjectId ): void {
		$statementId = $subjectId . StatementGuid::SEPARATOR . 'INVALID-STATEMENT-ID';

		try {
			$this->newReplaceStatementValidator()->assertValidRequest(
				$this->newUseCaseRequest( [
					'$subjectId' => $subjectId,
					'$statementId' => $statementId,
					'$statement' => [ 'valid' => 'statement' ],
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_STATEMENT_ID, $e->getErrorCode() );
			$this->assertSame( 'Not a valid statement ID: ' . $statementId, $e->getErrorMessage() );
		}
	}

	public function testValidate_withStatementInvalidField(): void {
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
			$this->newReplaceStatementValidator()->assertValidRequest(
				$this->newUseCaseRequest( [
					'$statementId' => 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
					'$statement' => $invalidStatement,
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::STATEMENT_DATA_INVALID_FIELD, $e->getErrorCode() );
			$this->assertSame( "Invalid input for 'some-field'", $e->getErrorMessage() );
			$this->assertSame( 'some-field', $e->getErrorContext()[UseCaseError::CONTEXT_PATH] );
			$this->assertSame( 'foo', $e->getErrorContext()[UseCaseError::CONTEXT_VALUE] );
		}
	}

	public function testValidate_withStatementMissingField(): void {
		$invalidStatement = [ 'this is' => 'not a valid statement' ];
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
			$this->newReplaceStatementValidator()->assertValidRequest(
				$this->newUseCaseRequest( [
					'$statementId' => 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
					'$statement' => $invalidStatement,
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::STATEMENT_DATA_MISSING_FIELD, $e->getErrorCode() );
			$this->assertSame(
				'Mandatory field missing in the statement data: some-field',
				$e->getErrorMessage()
			);
			$this->assertSame( [ UseCaseError::CONTEXT_PATH => 'some-field' ], $e->getErrorContext() );
		}
	}

	public function testValidate_withCommentTooLong(): void {
		$comment = str_repeat( 'x', CommentStore::COMMENT_CHARACTER_LIMIT + 1 );
		try {
			$this->newReplaceStatementValidator()->assertValidRequest(
				$this->newUseCaseRequest( [
					'$statementId' => 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
					'$statement' => [ 'valid' => 'statement' ],
					'$comment' => $comment,
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::COMMENT_TOO_LONG, $e->getErrorCode() );
			$this->assertSame(
				'Comment must not be longer than ' . CommentStore::COMMENT_CHARACTER_LIMIT . ' characters.',
				$e->getErrorMessage()
			);
		}
	}

	public function testValidate_withInvalidEditTags(): void {
		$invalid = 'invalid';

		try {
			$this->newReplaceStatementValidator()->assertValidRequest(
				$this->newUseCaseRequest( [
					'$statementId' => 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
					'$statement' => [ 'valid' => 'statement' ],
					'$editTags' => [ 'some', 'tags', 'are', $invalid ],
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_EDIT_TAG, $e->getErrorCode() );
			$this->assertSame( 'Invalid MediaWiki tag: "invalid"', $e->getErrorMessage() );
		}
	}

	public function provideStatementSubjectId(): Generator {
		yield 'item id' => [ 'Q123' ];
		yield 'property id' => [ 'P123' ];
	}

	private function newReplaceStatementValidator(): ReplaceStatementValidator {
		return new ReplaceStatementValidator(
			$this->requestedSubjectIdValidator,
			new StatementIdValidator( new BasicEntityIdParser() ),
			$this->statementValidator,
			new EditMetadataValidator( CommentStore::COMMENT_CHARACTER_LIMIT, self::ALLOWED_TAGS )
		);
	}

	private function newUseCaseRequest( array $requestData ): ReplaceStatementRequest {
		return new ReplaceStatementRequest(
			$requestData['$statementId'],
			$requestData['$statement'],
			$requestData['$editTags'] ?? [],
			$requestData['$isBot'] ?? false,
			$requestData['$comment'] ?? null,
			$requestData['$username'] ?? null,
			$requestData['$subjectId'] ?? null
		);
	}

}

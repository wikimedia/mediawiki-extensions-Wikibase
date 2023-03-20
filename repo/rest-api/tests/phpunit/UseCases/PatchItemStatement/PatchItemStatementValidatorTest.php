<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\PatchItemStatement;

use CommentStore;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementRequest;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementValidator;
use Wikibase\Repo\RestApi\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\JsonPatchValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchItemStatementValidatorTest extends TestCase {

	/**
	 * @var MockObject|JsonPatchValidator
	 */
	private $jsonPatchValidator;

	private const ALLOWED_TAGS = [ 'some', 'tags', 'are', 'allowed' ];

	protected function setUp(): void {
		parent::setUp();

		$this->jsonPatchValidator = $this->createStub( JsonPatchValidator::class );
		$this->jsonPatchValidator->method( 'validate' )->willReturn( null );
	}

	/**
	 * @dataProvider provideValidRequest
	 *
	 * @doesNotPerformAssertions
	 */
	public function testAssertValidRequest_withValidRequest( array $requestData ): void {
		$this->newPatchItemStatementValidator()->assertValidRequest(
			$this->newUseCaseRequest( $requestData )
		);
	}

	public function provideValidRequest(): Generator {
		$itemId = 'Q123';
		$statementId = $itemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		yield 'Valid with item ID' => [
			[
				'$statementId' => $statementId,
				'$patch' => [ 'valid' => 'patch' ],
				'$editTags' => [],
				'$isBot' => false,
				'$comment' => null,
				'$username' => null,
				'$itemId' => $itemId,
			],
		];
		yield 'Valid without item ID' => [
			[
				'$statementId' => $statementId,
				'$patch' => [ 'valid' => 'patch' ],
				'$editTags' => [],
				'$isBot' => false,
				'$comment' => null,
				'$username' => null,
			],
		];
	}

	public function testAssertValidRequest_withInvalidItemId(): void {
		$itemId = 'X123';
		try {
			$this->newPatchItemStatementValidator()->assertValidRequest(
				$this->newUseCaseRequest( [
					'$statementId' => 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
					'$patch' => [ 'valid' => 'patch' ],
					'$editTags' => [],
					'$isBot' => false,
					'$comment' => null,
					'$username' => null,
					'$itemId' => $itemId,
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $e->getErrorCode() );
			$this->assertSame(
				'Not a valid item ID: ' . $itemId,
				$e->getErrorMessage()
			);
		}
	}

	public function testAssertValidRequest_withInvalidStatementId(): void {
		$itemId = 'Q123';
		$statementId = $itemId . StatementGuid::SEPARATOR . 'INVALID-STATEMENT-ID';
		try {
			$this->newPatchItemStatementValidator()->assertValidRequest(
				$this->newUseCaseRequest( [
					'$statementId' => $statementId,
					'$patch' => [ 'valid' => 'patch' ],
					'$editTags' => [],
					'$isBot' => false,
					'$comment' => null,
					'$username' => null,
					'$itemId' => $itemId,
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_STATEMENT_ID, $e->getErrorCode() );
			$this->assertSame(
				'Not a valid statement ID: ' . $statementId,
				$e->getErrorMessage()
			);
		}
	}

	/**
	 * @dataProvider invalidPatchProvider
	 */
	public function testAssertValidRequest_withInvalidPatch(
		ValidationError $validationError,
		string $expectedErrorCode,
		string $expectedErrorMessage,
		?array $expectedErrorContext
	): void {
		$invalidPatch = [ 'this is' => 'not a valid patch' ];
		$this->jsonPatchValidator = $this->createMock( JsonPatchValidator::class );
		$this->jsonPatchValidator->method( 'validate' )
			->with( $invalidPatch )
			->willReturn( $validationError );

		try {
			$this->newPatchItemStatementValidator()->assertValidRequest(
				$this->newUseCaseRequest( [
					'$statementId' => 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
					'$patch' => $invalidPatch,
					'$editTags' => [],
					'$isBot' => false,
					'$comment' => null,
					'$username' => null,
					'$itemId' => null,
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedErrorCode, $e->getErrorCode() );
			$this->assertSame( $expectedErrorMessage, $e->getErrorMessage() );
			$this->assertSame( $expectedErrorContext, $e->getErrorContext() );
		}
	}

	public function invalidPatchProvider(): Generator {
		yield 'from invalid patch' => [
			new ValidationError( JsonPatchValidator::CODE_INVALID ),
			UseCaseError::INVALID_PATCH,
			'The provided patch is invalid',
			null,
		];

		$context = [
			JsonPatchValidator::CONTEXT_OPERATION => [ 'path' => '/a/b/c', 'value' => 'test' ],
			JsonPatchValidator::CONTEXT_FIELD => 'op',
		];
		yield 'from missing patch field' => [
			new ValidationError( JsonPatchValidator::CODE_MISSING_FIELD, $context ),
			UseCaseError::MISSING_JSON_PATCH_FIELD,
			"Missing 'op' in JSON patch",
			$context,
		];

		$context = [ JsonPatchValidator::CONTEXT_OPERATION => [ 'op' => 'bad', 'path' => '/a/b/c', 'value' => 'test' ] ];
		yield 'from invalid patch operation' => [
			new ValidationError( JsonPatchValidator::CODE_INVALID_OPERATION, $context ),
			UseCaseError::INVALID_PATCH_OPERATION,
			"Incorrect JSON patch operation: 'bad'",
			$context,
		];

		$context = [
			JsonPatchValidator::CONTEXT_OPERATION => [
				'op' => [ 'not', [ 'a' => 'string' ] ],
				'path' => '/a/b/c',
				'value' => 'test',
			],
			JsonPatchValidator::CONTEXT_FIELD => 'op',
		];
		yield 'from invalid patch field type' => [
			new ValidationError( JsonPatchValidator::CODE_INVALID_FIELD_TYPE, $context ),
			UseCaseError::INVALID_PATCH_FIELD_TYPE,
			"The value of 'op' must be of type string",
			$context,
		];
	}

	public function testAssertValidRequest_withCommentTooLong(): void {
		$comment = str_repeat( 'x', CommentStore::COMMENT_CHARACTER_LIMIT + 1 );
		try {
			$this->newPatchItemStatementValidator()->assertValidRequest(
				$this->newUseCaseRequest( [
					'$statementId' => 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
					'$patch' => [ 'valid' => 'patch' ],
					'$editTags' => [],
					'$isBot' => false,
					'$comment' => $comment,
					'$username' => null,
					'$itemId' => null,
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( EditMetadataValidator::CODE_COMMENT_TOO_LONG, $e->getErrorCode() );
			$this->assertSame(
				'Comment must not be longer than ' . CommentStore::COMMENT_CHARACTER_LIMIT . ' characters.',
				$e->getErrorMessage()
			);
		}
	}

	public function testAssertValidRequest_withInvalidEditTags(): void {
		$invalid = 'invalid';
		try {
			$this->newPatchItemStatementValidator()->assertValidRequest(
				$this->newUseCaseRequest( [
					'$statementId' => 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
					'$patch' => [ 'valid' => 'patch' ],
					'$editTags' => [ 'some', 'tags', 'are', $invalid ],
					'$isBot' => false,
					'$comment' => null,
					'$username' => null,
					'$itemId' => null,
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_EDIT_TAG, $e->getErrorCode() );
			$this->assertSame(
				'Invalid MediaWiki tag: "invalid"',
				$e->getErrorMessage()
			);
		}
	}

	private function newPatchItemStatementValidator(): PatchItemStatementValidator {
		return new PatchItemStatementValidator(
			new ItemIdValidator(),
			new StatementIdValidator( new ItemIdParser() ),
			$this->jsonPatchValidator,
			new EditMetadataValidator( CommentStore::COMMENT_CHARACTER_LIMIT, self::ALLOWED_TAGS )
		);
	}

	private function newUseCaseRequest( array $requestData ): PatchItemStatementRequest {
		return new PatchItemStatementRequest(
			$requestData['$statementId'],
			$requestData['$patch'],
			$requestData['$editTags'],
			$requestData['$isBot'],
			$requestData['$comment'] ?? null,
			$requestData['$username'] ?? null,
			$requestData['$itemId'] ?? null
		);
	}
}

<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchStatement;

use CommentStore;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\JsonPatchValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatementValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchStatementValidatorTest extends TestCase {

	private JsonPatchValidator $jsonPatchValidator;

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
		$this->newValidator()->assertValidRequest( $this->newUseCaseRequest( $requestData ) );
	}

	public static function provideValidRequest(): Generator {
		$statementId = 'Q123' . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		yield 'Valid with item ID' => [
			[
				'$statementId' => $statementId,
				'$patch' => [ 'valid' => 'patch' ],
				'$editTags' => [],
				'$isBot' => false,
				'$comment' => null,
				'$username' => null,
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

	public function testAssertValidRequest_withInvalidStatementId(): void {
		$statementId = 'Q123' . StatementGuid::SEPARATOR . 'INVALID-STATEMENT-ID';
		try {
			$this->newValidator()->assertValidRequest(
				$this->newUseCaseRequest( [
					'$statementId' => $statementId,
					'$patch' => [ 'valid' => 'patch' ],
					'$editTags' => [],
					'$isBot' => false,
					'$comment' => null,
					'$username' => null,
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_STATEMENT_ID, $e->getErrorCode() );
			$this->assertSame( "Not a valid statement ID: $statementId", $e->getErrorMessage() );
		}
	}

	/**
	 * @dataProvider invalidPatchProvider
	 */
	public function testAssertValidRequest_withInvalidPatch(
		ValidationError $validationError,
		string $expectedErrorCode,
		string $expectedErrorMessage,
		array $expectedErrorContext
	): void {
		$invalidPatch = [ 'this is' => 'not a valid patch' ];
		$this->jsonPatchValidator = $this->createMock( JsonPatchValidator::class );
		$this->jsonPatchValidator->method( 'validate' )
			->with( $invalidPatch )
			->willReturn( $validationError );

		try {
			$this->newValidator()->assertValidRequest(
				$this->newUseCaseRequest( [
					'$statementId' => 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
					'$patch' => $invalidPatch,
					'$editTags' => [],
					'$isBot' => false,
					'$comment' => null,
					'$username' => null,
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedErrorCode, $e->getErrorCode() );
			$this->assertSame( $expectedErrorMessage, $e->getErrorMessage() );
			$this->assertSame( $expectedErrorContext, $e->getErrorContext() );
		}
	}

	public static function invalidPatchProvider(): Generator {
		yield 'from invalid patch' => [
			new ValidationError( JsonPatchValidator::CODE_INVALID ),
			UseCaseError::INVALID_PATCH,
			'The provided patch is invalid',
			[],
		];

		$operation = [ 'path' => '/a/b/c', 'value' => 'test' ];
		yield 'from missing patch field' => [
			new ValidationError( JsonPatchValidator::CODE_MISSING_FIELD, [
				JsonPatchValidator::CONTEXT_OPERATION => $operation,
				JsonPatchValidator::CONTEXT_FIELD => 'op',
			] ),
			UseCaseError::MISSING_JSON_PATCH_FIELD,
			"Missing 'op' in JSON patch",
			[ 'operation' => $operation, 'field' => 'op' ],
		];

		$operation = [ 'op' => 'bad', 'path' => '/a/b/c', 'value' => 'test' ];
		yield 'from invalid patch operation' => [
			new ValidationError( JsonPatchValidator::CODE_INVALID_OPERATION, [ JsonPatchValidator::CONTEXT_OPERATION => $operation ] ),
			UseCaseError::INVALID_PATCH_OPERATION,
			"Incorrect JSON patch operation: 'bad'",
			[ 'operation' => $operation ],
		];

		$operation = [
			'op' => [ 'not', [ 'a' => 'string' ] ],
			'path' => '/a/b/c',
			'value' => 'test',
		];
		yield 'from invalid patch field type' => [
			new ValidationError( JsonPatchValidator::CODE_INVALID_FIELD_TYPE, [
				JsonPatchValidator::CONTEXT_OPERATION => $operation,
				JsonPatchValidator::CONTEXT_FIELD => 'op',
			] ),
			UseCaseError::INVALID_PATCH_FIELD_TYPE,
			"The value of 'op' must be of type string",
			[ 'operation' => $operation, 'field' => 'op' ],
		];
	}

	public function testAssertValidRequest_withCommentTooLong(): void {
		$comment = str_repeat( 'x', CommentStore::COMMENT_CHARACTER_LIMIT + 1 );
		try {
			$this->newValidator()->assertValidRequest(
				$this->newUseCaseRequest( [
					'$statementId' => 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
					'$patch' => [ 'valid' => 'patch' ],
					'$editTags' => [],
					'$isBot' => false,
					'$comment' => $comment,
					'$username' => null,
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

	public function testAssertValidRequest_withInvalidEditTags(): void {
		$invalid = 'invalid';
		try {
			$this->newValidator()->assertValidRequest(
				$this->newUseCaseRequest( [
					'$statementId' => 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
					'$patch' => [ 'valid' => 'patch' ],
					'$editTags' => [ 'some', 'tags', 'are', $invalid ],
					'$isBot' => false,
					'$comment' => null,
					'$username' => null,
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_EDIT_TAG, $e->getErrorCode() );
			$this->assertSame( 'Invalid MediaWiki tag: "invalid"', $e->getErrorMessage() );
		}
	}

	private function newValidator(): PatchStatementValidator {
		return new PatchStatementValidator(
			new StatementIdValidator( new ItemIdParser() ),
			$this->jsonPatchValidator,
			new EditMetadataValidator( CommentStore::COMMENT_CHARACTER_LIMIT, self::ALLOWED_TAGS )
		);
	}

	private function newUseCaseRequest( array $requestData ): PatchStatementRequest {
		return new PatchStatementRequest(
			$requestData['$statementId'],
			$requestData['$patch'],
			$requestData['$editTags'],
			$requestData['$isBot'],
			$requestData['$comment'] ?? null,
			$requestData['$username'] ?? null
		);
	}

}

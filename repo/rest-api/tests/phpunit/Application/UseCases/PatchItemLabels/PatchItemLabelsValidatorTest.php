<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchItemLabels;

use CommentStore;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchItemLabelsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchItemLabelsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\JsonPatchValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchItemLabelsValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchItemLabelsValidatorTest extends TestCase {

	private JsonPatchValidator $jsonPatchValidator;

	private const ALLOWED_TAGS = [ 'some', 'tags', 'are', 'allowed' ];

	protected function setUp(): void {
		parent::setUp();

		$this->jsonPatchValidator = $this->createStub( JsonPatchValidator::class );
		$this->jsonPatchValidator->method( 'validate' )->willReturn( null );
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testAssertValidRequest_withValidRequest(): void {
		$this->newPatchItemLabelsValidator()->assertValidRequest(
			new PatchItemLabelsRequest( 'Q123', [ 'valid' => 'patch' ], [], false, null, null )
		);
	}

	public function testAssertValidRequest_withInvalidItemId(): void {
		$itemId = 'X123';
		try {
			$this->newPatchItemLabelsValidator()->assertValidRequest(
				new PatchItemLabelsRequest( $itemId, [ 'valid' => 'patch' ], [], false, null, null )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $e->getErrorCode() );
			$this->assertSame( "Not a valid item ID: $itemId", $e->getErrorMessage() );
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
			$this->newPatchItemLabelsValidator()->assertValidRequest(
				new PatchItemLabelsRequest( 'Q123', $invalidPatch, [], false, null, null )
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

		$operation = [ 'op' => 'bad', 'path' => '/a/b/c', 'value' => 'test' ];
		yield 'from invalid patch operation' => [
			new ValidationError( JsonPatchValidator::CODE_INVALID_OPERATION, [ JsonPatchValidator::CONTEXT_OPERATION => $operation ] ),
			UseCaseError::INVALID_PATCH_OPERATION,
			"Incorrect JSON patch operation: 'bad'",
			[ UseCaseError::CONTEXT_OPERATION => $operation ],
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
			[
				UseCaseError::CONTEXT_OPERATION => $operation,
				UseCaseError::CONTEXT_FIELD => 'op',
			],
		];

		$operation = [ 'path' => '/a/b/c', 'value' => 'test' ];
		yield 'from missing patch field' => [
			new ValidationError( JsonPatchValidator::CODE_MISSING_FIELD, [
				JsonPatchValidator::CONTEXT_OPERATION => $operation,
				JsonPatchValidator::CONTEXT_FIELD => 'op',
			] ),
			UseCaseError::MISSING_JSON_PATCH_FIELD,
			"Missing 'op' in JSON patch",
			[
				UseCaseError::CONTEXT_OPERATION => $operation,
				UseCaseError::CONTEXT_FIELD => 'op',
			],
		];
	}

	public function testAssertValidRequest_withCommentTooLong(): void {
		$comment = str_repeat( 'x', CommentStore::COMMENT_CHARACTER_LIMIT + 1 );
		try {
			$this->newPatchItemLabelsValidator()->assertValidRequest(
				new PatchItemLabelsRequest( 'Q123', [ 'valid' => 'patch' ], [], false, $comment, null )
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
			$this->newPatchItemLabelsValidator()->assertValidRequest(
				new PatchItemLabelsRequest(
					'Q123',
					[ 'valid' => 'patch' ],
					[ 'some', 'tags', 'are', $invalid ],
					false,
					null,
					null
				)
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

	private function newPatchItemLabelsValidator(): PatchItemLabelsValidator {
		return new PatchItemLabelsValidator(
			new ItemIdValidator(),
			$this->jsonPatchValidator,
			new EditMetadataValidator( CommentStore::COMMENT_CHARACTER_LIMIT, self::ALLOWED_TAGS )
		);
	}

}

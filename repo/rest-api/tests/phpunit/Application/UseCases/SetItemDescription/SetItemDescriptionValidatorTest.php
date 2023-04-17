<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\SetItemDescription;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescriptionRequest;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Infrastructure\WikibaseRepoDescriptionLanguageCodeValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescriptionValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SetItemDescriptionValidatorTest extends TestCase {

	private const ALLOWED_TAGS = [ 'some', 'tags', 'are', 'allowed' ];
	private const COMMENT_CHARACTER_LIMIT = 50;

	private ItemDescriptionValidator $itemDescriptionValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->itemDescriptionValidator = $this->createStub( ItemDescriptionValidator::class );
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testValidate_withValidRequest(): void {
		$this->newSetItemDescriptionValidator()->assertValidRequest( $this->newUseCaseRequest() );
	}

	/**
	 * @dataProvider provideInvalidRequest
	 */
	public function testValidate_withInvalidRequest(
		array $request,
		string $errorCode,
		string $errorMessage,
		array $context = null
	): void {
		try {
			$this->newSetItemDescriptionValidator()->assertValidRequest(
				$this->newUseCaseRequest( $request )
			);

			$this->fail( 'Exception was not thrown.' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $errorCode, $e->getErrorCode() );
			$this->assertSame( $errorMessage, $e->getErrorMessage() );
			$this->assertSame( $context, $e->getErrorContext() );
		}
	}

	public function provideInvalidRequest(): Generator {
		yield 'invalid item id' => [
			[ '$itemId' => 'X123' ],
			UseCaseError::INVALID_ITEM_ID,
			'Not a valid item ID: X123',
		];

		yield 'invalid language code' => [
			[ '$languageCode' => 'xyz' ],
			UseCaseError::INVALID_LANGUAGE_CODE,
			'Not a valid language code: xyz',
		];

		yield 'invalid edit tags' => [
			[ '$editTags' => [ 'some', 'tags', 'are', 'invalid' ] ],
			UseCaseError::INVALID_EDIT_TAG,
			'Invalid MediaWiki tag: "invalid"',
		];

		yield 'comment too long' => [
			[ '$comment' => str_repeat( 'x', self::COMMENT_CHARACTER_LIMIT + 1 ) ],
			UseCaseError::COMMENT_TOO_LONG,
			'Comment must not be longer than ' . self::COMMENT_CHARACTER_LIMIT . ' characters.',
		];
	}

	/**
	 * @dataProvider provideInvalidDescription
	 */
	public function testValidate_withInvalidDescription(
		ValidationError $validationError,
		string $errorCode,
		string $errorMessage,
		array $context = null
	): void {
		$this->itemDescriptionValidator = $this->createStub( ItemDescriptionValidator::class );
		$this->itemDescriptionValidator->method( 'validate' )->willReturn( $validationError );

		try {
			$this->newSetItemDescriptionValidator()->assertValidRequest( $this->newUseCaseRequest() );

			$this->fail( 'Exception was not thrown.' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $errorCode, $e->getErrorCode() );
			$this->assertSame( $errorMessage, $e->getErrorMessage() );
			$this->assertSame( $context, $e->getErrorContext() );
		}
	}

	public function provideInvalidDescription(): Generator {
		yield 'description empty' => [
			new ValidationError( ItemDescriptionValidator::CODE_EMPTY ),
			UseCaseError::DESCRIPTION_EMPTY,
			'Description must not be empty',
		];

		$description = 'Label Description';
		$limit = 40;
		yield 'description too long' => [
			new ValidationError(
				ItemDescriptionValidator::CODE_TOO_LONG,
				[
					ItemDescriptionValidator::CONTEXT_VALUE => $description,
					ItemDescriptionValidator::CONTEXT_LIMIT => $limit,
				]
			),
			UseCaseError::DESCRIPTION_TOO_LONG,
			'Description must be no more than 40 characters long',
			[ 'value' => $description, 'character-limit' => $limit ],
		];

		$description = "tab characters \t not allowed";
		yield 'invalid description' => [
			new ValidationError(
				ItemDescriptionValidator::CODE_INVALID,
				[ ItemDescriptionValidator::CONTEXT_VALUE => $description ],
			),
			UseCaseError::INVALID_DESCRIPTION,
			"Not a valid description: $description",
		];
	}

	private function newSetItemDescriptionValidator(): SetItemDescriptionValidator {
		return new SetItemDescriptionValidator(
			new ItemIdValidator(),
			new WikibaseRepoDescriptionLanguageCodeValidator(
				WikibaseRepo::getTermValidatorFactory()
			),
			$this->itemDescriptionValidator,
			new EditMetadataValidator( self::COMMENT_CHARACTER_LIMIT, self::ALLOWED_TAGS )
		);
	}

	private function newUseCaseRequest( array $requestData = [] ): SetItemDescriptionRequest {
		return new SetItemDescriptionRequest(
			$requestData['$itemId'] ?? 'Q123',
			$requestData['$languageCode'] ?? 'en',
			$requestData['$description'] ?? 'Label Description',
			$requestData['$editTags'] ?? [],
			$requestData['$isBot'] ?? false,
			$requestData['$comment'] ?? null,
			$requestData['$user'] ?? null
		);
	}

}

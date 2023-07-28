<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\SetItemLabel;

use CommentStore;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabelValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabelValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SetItemLabelValidatorTest extends TestCase {

	private const ALLOWED_TAGS = [ 'some', 'tags', 'are', 'allowed' ];
	private ItemLabelValidator $itemLabelValidator;

	protected function setUp(): void {
		parent::setUp();
		$this->itemLabelValidator = $this->createStub( ItemLabelValidator::class );
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testValid(): void {
		$itemId = 'Q123';
		$langCode = 'en';
		$newLabelText = 'New label';
		$editTags = [ 'some', 'tags' ];
		$isBot = false;
		$comment = "{$this->getName()} Comment";

		$request = new SetItemLabelRequest( $itemId, $langCode, $newLabelText, $editTags, $isBot, $comment, null );

		$this->newValidator()->assertValidRequest( $request );
	}

	public function testWithInvalidId(): void {
		$invalidItemId = 'X123';
		$langCode = 'en';
		$newLabelText = 'New label';
		$editTags = [ 'some', 'tags' ];
		$isBot = false;
		$comment = "{$this->getName()} Comment";

		$request = new SetItemLabelRequest( $invalidItemId, $langCode, $newLabelText, $editTags, $isBot, $comment, null );

		try {
			$this->newValidator()->assertValidRequest( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $error->getErrorCode() );
			$this->assertSame( "Not a valid item ID: $invalidItemId", $error->getErrorMessage() );
		}
	}

	public function testWithInvalidLanguageCode(): void {
		$itemId = 'Q123';
		$invalidLanguageCode = 'xyz';
		$newLabelText = 'New label';
		$editTags = [ 'some', 'tags' ];
		$isBot = false;
		$comment = "{$this->getName()} Comment";

		$request = new SetItemLabelRequest( $itemId, $invalidLanguageCode, $newLabelText, $editTags, $isBot, $comment, null );

		try {
			$this->newValidator()->assertValidRequest( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertSame( UseCaseError::INVALID_LANGUAGE_CODE, $error->getErrorCode() );
			$this->assertSame( "Not a valid language code: $invalidLanguageCode", $error->getErrorMessage() );
		}
	}

	public function testWithInvalidEditTag(): void {
		$invalid = 'invalid';

		$itemId = 'Q123';
		$languageCode = 'en';
		$newLabelText = 'New label';
		$editTags = [ 'some', 'tags', 'are', $invalid ];
		$isBot = false;
		$comment = "{$this->getName()} Comment";

		$request = new SetItemLabelRequest( $itemId, $languageCode, $newLabelText, $editTags, $isBot, $comment, null );

		try {
			$this->newValidator()->assertValidRequest( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertSame( UseCaseError::INVALID_EDIT_TAG, $error->getErrorCode() );
			$this->assertSame( 'Invalid MediaWiki tag: "invalid"', $error->getErrorMessage() );
		}
	}

	public function testWithCommentTooLong(): void {
		$itemId = 'Q123';
		$languageCode = 'en';
		$newLabelText = 'New label';
		$editTags = [ 'some', 'tags' ];
		$isBot = false;
		$comment = str_repeat( 'x', CommentStore::COMMENT_CHARACTER_LIMIT + 1 );

		$request = new SetItemLabelRequest( $itemId, $languageCode, $newLabelText, $editTags, $isBot, $comment, null );

		try {
			$this->newValidator()->assertValidRequest( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertSame( UseCaseError::COMMENT_TOO_LONG, $error->getErrorCode() );
			$this->assertSame(
				'Comment must not be longer than ' . CommentStore::COMMENT_CHARACTER_LIMIT . ' characters.',
				$error->getErrorMessage()
			);
		}
	}

	/**
	 * @dataProvider invalidLabelProvider
	 */
	public function testWithInvalidLabel(
		ValidationError $validationError,
		string $expectedErrorCode,
		string $expectedErrorMessage,
		array $expectedContext = []
	): void {
		$itemId = 'Q123';
		$languageCode = 'en';
		$labelText = 'my label';
		$editTags = [ 'some', 'tags' ];
		$isBot = false;
		$comment = 'Empty label';

		$request = new SetItemLabelRequest( $itemId, $languageCode, $labelText, $editTags, $isBot, $comment, null );

		$this->itemLabelValidator = $this->createStub( ItemLabelValidator::class );
		$this->itemLabelValidator->method( 'validate' )->willReturn( $validationError );

		try {
			$this->newValidator()->assertValidRequest( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertSame( $expectedErrorCode, $error->getErrorCode() );
			$this->assertSame( $expectedErrorMessage, $error->getErrorMessage() );
			$this->assertSame( $expectedContext, $error->getErrorContext() );
		}
	}

	public static function invalidLabelProvider(): Generator {
		$label = "tab characters \t not allowed";
		yield 'invalid label' => [
			new ValidationError(
				ItemLabelValidator::CODE_INVALID,
				[ ItemLabelValidator::CONTEXT_VALUE => $label ],
			),
			UseCaseError::INVALID_LABEL,
			"Not a valid label: $label",
		];

		yield 'label empty' => [
			new ValidationError( ItemLabelValidator::CODE_EMPTY ),
			UseCaseError::LABEL_EMPTY,
			'Label must not be empty',
		];

		$label = 'This label is too long.';
		$limit = 250;
		yield 'label too long' => [
			new ValidationError( ItemLabelValidator::CODE_TOO_LONG, [
				ItemLabelValidator::CONTEXT_VALUE => $label,
				ItemLabelValidator::CONTEXT_LIMIT => $limit,
			] ),
			UseCaseError::LABEL_TOO_LONG,
			"Label must be no more than $limit characters long",
			[
				UseCaseError::CONTEXT_VALUE => $label,
				UseCaseError::CONTEXT_CHARACTER_LIMIT => $limit,
			],
		];

		$language = 'en';
		yield 'label equals description' => [
			new ValidationError(
				ItemLabelValidator::CODE_LABEL_DESCRIPTION_EQUAL,
				[ ItemLabelValidator::CONTEXT_LANGUAGE => $language ]
			),
			UseCaseError::LABEL_DESCRIPTION_SAME_VALUE,
			"Label and description for language code '$language' can not have the same value.",
			[ UseCaseError::CONTEXT_LANGUAGE => $language ],
		];

		$language = 'en';
		$label = 'My Label';
		$description = 'My Description';
		$itemId = 'Q456';
		yield 'label/description not unique' => [
			new ValidationError( ItemLabelValidator::CODE_LABEL_DESCRIPTION_DUPLICATE, [
				ItemLabelValidator::CONTEXT_LANGUAGE => $language,
				ItemLabelValidator::CONTEXT_LABEL => $label,
				ItemLabelValidator::CONTEXT_DESCRIPTION => $description,
				ItemLabelValidator::CONTEXT_MATCHING_ITEM_ID => $itemId,
			] ),
			UseCaseError::ITEM_LABEL_DESCRIPTION_DUPLICATE,
			"Item $itemId already has label '$label' associated with language code '$language', using the same description text.",
			[
				UseCaseError::CONTEXT_LANGUAGE => $language,
				UseCaseError::CONTEXT_LABEL => $label,
				UseCaseError::CONTEXT_DESCRIPTION => $description,
				UseCaseError::CONTEXT_MATCHING_ITEM_ID => $itemId,
			],
		];
	}

	private function newValidator(): SetItemLabelValidator {
		return new SetItemLabelValidator(
			new ItemIdValidator(),
			new LanguageCodeValidator( WikibaseRepo::getTermsLanguages()->getLanguages() ),
			new EditMetadataValidator( CommentStore::COMMENT_CHARACTER_LIMIT, self::ALLOWED_TAGS ),
			$this->itemLabelValidator
		);
	}

}

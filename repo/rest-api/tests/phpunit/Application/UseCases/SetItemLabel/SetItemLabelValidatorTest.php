<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\SetItemLabel;

use CommentStore;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabelValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\LabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
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

		$this->newLabelValidator()->assertValidRequest( $request );
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
			$this->newLabelValidator()->assertValidRequest( $request );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $error->getErrorCode() );
			$this->assertSame( 'Not a valid item ID: ' . $invalidItemId, $error->getErrorMessage() );
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
			$this->newLabelValidator()->assertValidRequest( $request );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertSame( UseCaseError::INVALID_LANGUAGE_CODE, $error->getErrorCode() );
			$this->assertSame( 'Not a valid language code: ' . $invalidLanguageCode, $error->getErrorMessage() );
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
			$this->newLabelValidator()->assertValidRequest( $request );

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
			$this->newLabelValidator()->assertValidRequest( $request );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertSame( UseCaseError::COMMENT_TOO_LONG, $error->getErrorCode() );
			$this->assertSame(
				'Comment must not be longer than ' . CommentStore::COMMENT_CHARACTER_LIMIT . ' characters.',
				$error->getErrorMessage()
			);
		}
	}

	public function testWithLabelEmpty(): void {
		$itemId = 'Q123';
		$languageCode = 'en';
		$emptyLabelText = '';
		$editTags = [ 'some', 'tags' ];
		$isBot = false;
		$comment = 'Empty label';

		$request = new SetItemLabelRequest( $itemId, $languageCode, $emptyLabelText, $editTags, $isBot, $comment, null );

		try {
			$this->newLabelValidator()->assertValidRequest( $request );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertSame( UseCaseError::LABEL_EMPTY, $error->getErrorCode() );
			$this->assertSame(
				'Label must not be empty',
				$error->getErrorMessage()
			);
		}
	}

	public function testWithLabelTooLong(): void {
		$itemId = 'Q123';
		$languageCode = 'en';
		$maxLabelLength = 5;
		$tooLongLabelText = str_repeat( 'x', $maxLabelLength + 1 );
		$editTags = [ 'some', 'tags' ];
		$isBot = false;
		$comment = 'Too long label';

		$request = new SetItemLabelRequest( $itemId, $languageCode, $tooLongLabelText, $editTags, $isBot, $comment, null );

		try {
			$this->newLabelValidator( $maxLabelLength )->assertValidRequest( $request );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertSame( UseCaseError::LABEL_TOO_LONG, $error->getErrorCode() );
			$this->assertSame(
				"Label must be no more than $maxLabelLength characters long",
				$error->getErrorMessage()
			);
		}
	}

	private function newLabelValidator( int $maxLabelLength = 250 ): SetItemLabelValidator {
		return ( new SetItemLabelValidator(
			new ItemIdValidator(),
			new LanguageCodeValidator( WikibaseRepo::getTermsLanguages()->getLanguages() ),
			new EditMetadataValidator(
				CommentStore::COMMENT_CHARACTER_LIMIT,
				self::ALLOWED_TAGS
			),
			new LabelValidator( $maxLabelLength )
		) );
	}

}

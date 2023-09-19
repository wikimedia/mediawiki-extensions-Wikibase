<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use MediaWiki\CommentStore\CommentStore;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\EditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\EditMetadataRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Model\UserProvidedEditMetadata;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\EditMetadataRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EditMetadataRequestValidatingDeserializerTest extends TestCase {

	public function testGivenValidRequest_returnsEditMetadata(): void {
		$user = 'potato';
		$isBot = false;
		$editTags = [ 'allowed' ];
		$comment = 'edit comment';
		$request = $this->createStub( EditMetadataRequest::class );
		$request->method( 'getUsername' )->willReturn( $user );
		$request->method( 'isBot' )->willReturn( $isBot );
		$request->method( 'getComment' )->willReturn( $comment );
		$request->method( 'getEditTags' )->willReturn( $editTags );

		$this->assertEquals(
			new UserProvidedEditMetadata( User::withUsername( $user ), $isBot, $comment, $editTags ),
			( new EditMetadataRequestValidatingDeserializer( $this->createStub( EditMetadataValidator::class ) ) )
				->validateAndDeserialize( $request )
		);
	}

	public function testWithCommentTooLong(): void {
		$comment = str_repeat( 'x', CommentStore::COMMENT_CHARACTER_LIMIT + 1 );
		$request = $this->createStub( EditMetadataRequest::class );
		$request->method( 'getComment' )->willReturn( $comment );
		$expectedError = new ValidationError(
			EditMetadataValidator::CODE_COMMENT_TOO_LONG,
			[ EditMetadataValidator::CONTEXT_COMMENT_MAX_LENGTH => CommentStore::COMMENT_CHARACTER_LIMIT ]
		);

		$editMetadataValidator = $this->createMock( EditMetadataValidator::class );
		$editMetadataValidator->method( 'validateComment' )
			->with( $comment )
			->willReturn( $expectedError );

		try {
			( new EditMetadataRequestValidatingDeserializer( $editMetadataValidator ) )->validateAndDeserialize( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::COMMENT_TOO_LONG, $e->getErrorCode() );
			$this->assertSame(
				'Comment must not be longer than ' . CommentStore::COMMENT_CHARACTER_LIMIT . ' characters.',
				$e->getErrorMessage()
			);
		}
	}

	public function testWithInvalidEditTags(): void {
		$invalidTags = [ 'bad', 'tags' ];
		$request = $this->createStub( EditMetadataRequest::class );
		$request->method( 'getEditTags' )->willReturn( $invalidTags );

		$validationError = new ValidationError(
			EditMetadataValidator::CODE_INVALID_TAG,
			[ EditMetadataValidator::CONTEXT_TAG_VALUE => json_encode( $invalidTags ) ]
		);

		$editMetadataValidator = $this->createMock( EditMetadataValidator::class );
		$editMetadataValidator->method( 'validateEditTags' )
			->with( $invalidTags )
			->willReturn( $validationError );

		try {
			( new EditMetadataRequestValidatingDeserializer( $editMetadataValidator ) )->validateAndDeserialize( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_EDIT_TAG, $e->getErrorCode() );
			$this->assertSame( 'Invalid MediaWiki tag: ["bad","tags"]', $e->getErrorMessage() );
		}
	}

}

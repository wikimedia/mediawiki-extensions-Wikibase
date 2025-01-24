<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation;

use MediaWiki\CommentStore\CommentStore;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\EditMetadataRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\EditMetadataRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ValidationError;
use Wikibase\Repo\Domains\Crud\Domain\Model\User;
use Wikibase\Repo\Domains\Crud\Domain\Model\UserProvidedEditMetadata;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\EditMetadataRequestValidatingDeserializer
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
			$this->assertSame( UseCaseError::VALUE_TOO_LONG, $e->getErrorCode() );
			$this->assertSame( 'The input value is too long', $e->getErrorMessage() );
			$this->assertSame(
				[ UseCaseError::CONTEXT_PATH => '/comment', UseCaseError::CONTEXT_LIMIT => CommentStore::COMMENT_CHARACTER_LIMIT ],
				$e->getErrorContext()
			);
		}
	}

	public function testWithInvalidEditTags(): void {
		$invalidTag = 'bad tag';
		$tags = [ 'good tag', $invalidTag ];
		$request = $this->createStub( EditMetadataRequest::class );
		$request->method( 'getEditTags' )->willReturn( $tags );

		$validationError = new ValidationError(
			EditMetadataValidator::CODE_INVALID_TAG,
			[ EditMetadataValidator::CONTEXT_TAG_VALUE => $invalidTag ]
		);

		$editMetadataValidator = $this->createMock( EditMetadataValidator::class );
		$editMetadataValidator->method( 'validateEditTags' )
			->with( $tags )
			->willReturn( $validationError );

		try {
			( new EditMetadataRequestValidatingDeserializer( $editMetadataValidator ) )->validateAndDeserialize( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newInvalidValue( '/tags/1' ), $e );
		}
	}

}

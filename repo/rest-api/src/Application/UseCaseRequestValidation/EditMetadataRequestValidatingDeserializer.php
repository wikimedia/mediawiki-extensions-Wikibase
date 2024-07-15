<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Model\UserProvidedEditMetadata;

/**
 * @license GPL-2.0-or-later
 */
class EditMetadataRequestValidatingDeserializer {

	private EditMetadataValidator $validator;

	public function __construct( EditMetadataValidator $validator ) {
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( EditMetadataRequest $request ): UserProvidedEditMetadata {
		$this->validateComment( $request->getComment() );
		$this->validateEditTags( $request->getEditTags() );

		return new UserProvidedEditMetadata(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$request->getUsername() === null ? User::newAnonymous() : User::withUsername( $request->getUsername() ),
			$request->isBot(),
			$request->getComment(),
			$request->getEditTags()
		);
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateEditTags( array $editTags ): void {
		$validationError = $this->validator->validateEditTags( $editTags );
		if ( $validationError ) {
			$tagIndex = array_search( $validationError->getContext()[EditMetadataValidator::CONTEXT_TAG_VALUE], $editTags );
			throw UseCaseError::newInvalidValue( "/tags/$tagIndex" );
		}
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateComment( ?string $comment ): void {
		if ( $comment === null ) {
			return;
		}

		$validationError = $this->validator->validateComment( $comment );
		if ( $validationError ) {
			$commentMaxLength = $validationError->getContext()[EditMetadataValidator::CONTEXT_COMMENT_MAX_LENGTH];
			throw UseCaseError::newValueTooLong( '/comment', $commentMaxLength );
		}
	}

}

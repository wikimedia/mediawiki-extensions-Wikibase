<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class RemoveStatementValidator {

	private StatementIdValidator $statementIdValidator;
	private EditMetadataValidator $editMetadataValidator;

	public function __construct(
		StatementIdValidator $statementIdValidator,
		EditMetadataValidator $editMetadataValidator
	) {
		$this->statementIdValidator = $statementIdValidator;
		$this->editMetadataValidator = $editMetadataValidator;
	}

	public function assertValidRequest( RemoveStatementRequest $request ): void {
		$this->assertValidStatementId( $request->getStatementId() );
		$this->assertValidComment( $request->getComment() );
		$this->assertValidEditTags( $request->getEditTags() );
	}

	private function assertValidStatementId( string $statementId ): void {
		$validationError = $this->statementIdValidator->validate( $statementId );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_STATEMENT_ID,
				"Not a valid statement ID: {$validationError->getContext()[StatementIdValidator::CONTEXT_VALUE]}"
			);
		}
	}

	private function assertValidComment( ?string $comment ): void {
		$validationError = $this->editMetadataValidator->validateComment( $comment );
		if ( $validationError ) {
			$commentMaxLength = $validationError->getContext()[ EditMetadataValidator::CONTEXT_COMMENT_MAX_LENGTH ];
			throw new UseCaseError(
				UseCaseError::COMMENT_TOO_LONG,
				"Comment must not be longer than $commentMaxLength characters."
			);
		}
	}

	private function assertValidEditTags( array $editTags ): void {
		$validationError = $this->editMetadataValidator->validateEditTags( $editTags );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_EDIT_TAG,
				"Invalid MediaWiki tag: {$validationError->getContext()[EditMetadataValidator::CONTEXT_TAG_VALUE]}"
			);
		}
	}

}

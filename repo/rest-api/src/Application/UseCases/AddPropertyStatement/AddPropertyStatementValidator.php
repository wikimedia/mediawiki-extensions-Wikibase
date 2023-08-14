<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement;

use LogicException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;

/**
 * @license GPL-2.0-or-later
 */
class AddPropertyStatementValidator {

	private PropertyIdValidator $propertyIdValidator;
	private StatementValidator $statementValidator;
	private EditMetadataValidator $editMetadataValidator;

	public function __construct(
		PropertyIdValidator $propertyIdValidator,
		StatementValidator $statementValidator,
		EditMetadataValidator $editMetadataValidator
	) {
		$this->propertyIdValidator = $propertyIdValidator;
		$this->statementValidator = $statementValidator;
		$this->editMetadataValidator = $editMetadataValidator;
	}

	public function assertValidRequest( AddPropertyStatementRequest $request ): void {
		$this->validatePropertyId( $request->getPropertyId() );
		$this->validateStatement( $request->getStatement() );
		$this->validateEditTags( $request->getEditTags() );
		$this->validateComment( $request->getComment() );
	}

	/**
	 * @throws UseCaseError
	 */
	private function validatePropertyId( string $propertyId ): void {
		$validationError = $this->propertyIdValidator->validate( $propertyId );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_PROPERTY_ID,
				"Not a valid property ID: {$validationError->getContext()[PropertyIdValidator::CONTEXT_VALUE]}",
				[ UseCaseError::CONTEXT_PROPERTY_ID => $validationError->getContext()[PropertyIdValidator::CONTEXT_VALUE] ]
			);
		}
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateStatement( array $statement ): void {
		$validationError = $this->statementValidator->validate( $statement );
		if ( $validationError ) {
			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case StatementValidator::CODE_INVALID_FIELD:
					throw new UseCaseError(
						UseCaseError::STATEMENT_DATA_INVALID_FIELD,
						"Invalid input for '{$context[StatementValidator::CONTEXT_FIELD_NAME]}'",
						[
							UseCaseError::CONTEXT_PATH => $context[StatementValidator::CONTEXT_FIELD_NAME],
							UseCaseError::CONTEXT_VALUE => $context[StatementValidator::CONTEXT_FIELD_VALUE],
						]
					);
				case StatementValidator::CODE_MISSING_FIELD:
					throw new UseCaseError(
						UseCaseError::STATEMENT_DATA_MISSING_FIELD,
						"Mandatory field missing in the statement data: {$context[StatementValidator::CONTEXT_FIELD_NAME]}",
						[ UseCaseError::CONTEXT_PATH => $context[StatementValidator::CONTEXT_FIELD_NAME] ]
					);
				default:
					throw new LogicException( "Unknown validation error code: {$validationError->getCode()}" );
			}
		}
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateEditTags( array $editTags ): void {
		$validationError = $this->editMetadataValidator->validateEditTags( $editTags );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_EDIT_TAG,
				"Invalid MediaWiki tag: {$validationError->getContext()[EditMetadataValidator::CONTEXT_TAG_VALUE]}"
			);
		}
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateComment( ?string $comment ): void {
		if ( $comment === null ) {
			return;
		}

		$validationError = $this->editMetadataValidator->validateComment( $comment );
		if ( $validationError ) {
			$commentMaxLength = $validationError->getContext()[EditMetadataValidator::CONTEXT_COMMENT_MAX_LENGTH];
			throw new UseCaseError(
				UseCaseError::COMMENT_TOO_LONG,
				"Comment must not be longer than $commentMaxLength characters.",
			);
		}
	}

	public function getValidatedStatement(): Statement {
		return $this->statementValidator->getValidatedStatement();
	}

}

<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemStatement;

use Wikibase\Repo\RestApi\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatementValidator {

	private StatementIdValidator $statementIdValidator;
	private ItemIdValidator $itemIdValidator;

	public function __construct(
		StatementIdValidator $statementIdValidator,
		ItemIdValidator $itemIdValidator
	) {
		$this->statementIdValidator = $statementIdValidator;
		$this->itemIdValidator = $itemIdValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function assertValidRequest( GetItemStatementRequest $statementRequest ): void {
		$statementIdValidationError = $this->statementIdValidator->validate(
			$statementRequest->getStatementId()
		);

		if ( $statementIdValidationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_STATEMENT_ID,
				'Not a valid statement ID: ' . $statementIdValidationError->getContext()[StatementIdValidator::CONTEXT_VALUE]
			);
		}

		$this->validateItemId( $statementRequest->getItemId() );
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateItemId( ?string $itemId ): void {
		if ( !isset( $itemId ) ) {
			return;
		}

		$validationError = $this->itemIdValidator->validate( $itemId );

		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_ITEM_ID,
				'Not a valid item ID: ' . $validationError->getContext()[ItemIdValidator::CONTEXT_VALUE]
			);
		}
	}

}

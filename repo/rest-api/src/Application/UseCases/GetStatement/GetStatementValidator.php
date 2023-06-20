<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetStatement;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetStatementValidator {

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
	public function assertValidRequest( GetStatementRequest $statementRequest ): void {
		$statementIdValidationError = $this->statementIdValidator->validate(
			$statementRequest->getStatementId()
		);

		if ( $statementIdValidationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_STATEMENT_ID,
				'Not a valid statement ID: ' . $statementIdValidationError->getContext()[StatementIdValidator::CONTEXT_VALUE]
			);
		}

		$this->validateItemId( $statementRequest->getEntityId() );
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

<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemStatement;

use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

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

	public function validate( GetItemStatementRequest $statementRequest ): ?ValidationError {
		return $this->statementIdValidator->validate(
			$statementRequest->getStatementId()
		) ?: $this->validateItemId( $statementRequest->getItemId() );
	}

	private function validateItemId( ?string $itemId ): ?ValidationError {
		return $itemId ? $this->itemIdValidator->validate( $itemId ) : null;
	}

}

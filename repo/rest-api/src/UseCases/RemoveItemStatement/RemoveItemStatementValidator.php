<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\RemoveItemStatement;

use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class RemoveItemStatementValidator {

	private StatementIdValidator $statementIdValidator;
	private ItemIdValidator $itemIdValidator;
	private EditMetadataValidator $editMetadataValidator;

	public function __construct(
		ItemIdValidator $itemIdValidator,
		StatementIdValidator $statementIdValidator,
		EditMetadataValidator $editMetadataValidator
	) {
		$this->itemIdValidator = $itemIdValidator;
		$this->statementIdValidator = $statementIdValidator;
		$this->editMetadataValidator = $editMetadataValidator;
	}

	public function validate( RemoveItemStatementRequest $request ): ?ValidationError {
		return $this->validateItemId( $request->getItemId() ) ?:
			$this->statementIdValidator->validate( $request->getStatementId() ) ?:
				$this->editMetadataValidator->validateComment( $request->getComment() ) ?:
					$this->editMetadataValidator->validateEditTags( $request->getEditTags() );
	}

	private function validateItemId( ?string $itemId ): ?ValidationError {
		return $itemId ? $this->itemIdValidator->validate( $itemId ) : null;
	}
}

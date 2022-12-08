<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\PatchItemStatement;

use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\JsonPatchValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemStatementValidator {

	private ItemIdValidator $itemIdValidator;
	private StatementIdValidator $statementIdValidator;
	private JsonPatchValidator $jsonPatchValidator;
	private EditMetadataValidator $editMetadataValidator;

	public function __construct(
		ItemIdValidator $itemIdValidator,
		StatementIdValidator $statementIdValidator,
		JsonPatchValidator $jsonPatchValidator,
		EditMetadataValidator $editMetadataValidator
	) {
		$this->itemIdValidator = $itemIdValidator;
		$this->statementIdValidator = $statementIdValidator;
		$this->jsonPatchValidator = $jsonPatchValidator;
		$this->editMetadataValidator = $editMetadataValidator;
	}

	public function validate( PatchItemStatementRequest $request ): ?ValidationError {
		return $this->validateItemId( $request->getItemId() ) ?:
			$this->statementIdValidator->validate( $request->getStatementId() ) ?:
				$this->jsonPatchValidator->validate( $request->getPatch() ) ?:
					$this->editMetadataValidator->validateEditTags( $request->getEditTags() ) ?:
						$this->editMetadataValidator->validateComment( $request->getComment() );
	}

	private function validateItemId( ?string $itemId ): ?ValidationError {
		return $itemId ? $this->itemIdValidator->validate( $itemId ) : null;
	}
}

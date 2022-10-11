<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\PatchItemStatement;

use Wikibase\Repo\RestApi\Domain\Services\JsonPatchValidator;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemStatementValidator {

	public const SOURCE_ITEM_ID = 'item ID';
	public const SOURCE_STATEMENT_ID = 'statement ID';
	public const SOURCE_PATCH = 'patch';
	public const SOURCE_COMMENT = 'comment';
	public const SOURCE_EDIT_TAGS = 'edit tags';

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
			$this->statementIdValidator->validate( $request->getStatementId(), self::SOURCE_STATEMENT_ID ) ?:
				$this->jsonPatchValidator->validate( $request->getPatch(), self::SOURCE_PATCH ) ?:
					$this->editMetadataValidator->validateEditTags( $request->getEditTags(), self::SOURCE_EDIT_TAGS ) ?:
						$this->editMetadataValidator->validateComment( $request->getComment(), self::SOURCE_COMMENT );
	}

	private function validateItemId( ?string $itemId ): ?ValidationError {
		return $itemId ? $this->itemIdValidator->validate( $itemId, self::SOURCE_ITEM_ID ) : null;
	}
}

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

	public const SOURCE_ITEM_ID = 'item ID';
	public const SOURCE_STATEMENT_ID = 'statement ID';
	public const SOURCE_COMMENT = 'comment';
	public const SOURCE_EDIT_TAGS = 'edit tags';

	private $statementIdValidator;
	private $itemIdValidator;
	private $editMetadataValidator;

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
			$this->statementIdValidator->validate( $request->getStatementId(), self::SOURCE_STATEMENT_ID ) ?:
				$this->editMetadataValidator->validateComment( $request->getComment(), self::SOURCE_COMMENT ) ?:
					$this->editMetadataValidator->validateEditTags( $request->getEditTags(), self::SOURCE_EDIT_TAGS );
	}

	private function validateItemId( ?string $itemId ): ?ValidationError {
		return $itemId ? $this->itemIdValidator->validate( $itemId, self::SOURCE_ITEM_ID ) : null;
	}
}

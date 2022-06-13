<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\AddItemStatement;

use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class AddItemStatementValidator {

	public const SOURCE_ITEM_ID = 'item ID';
	public const SOURCE_STATEMENT = 'statement';
	public const SOURCE_EDIT_TAGS = 'edit tags';

	private $statementValidator;
	private $itemIdValidator;
	private $editMetadataValidator;

	public function __construct(
		ItemIdValidator $itemIdValidator,
		StatementValidator $statementValidator,
		EditMetadataValidator $editMetadataValidator
	) {
		$this->itemIdValidator = $itemIdValidator;
		$this->statementValidator = $statementValidator;
		$this->editMetadataValidator = $editMetadataValidator;
	}

	public function validate( AddItemStatementRequest $request ): ?ValidationError {
		return $this->itemIdValidator->validate( $request->getItemId(), self::SOURCE_ITEM_ID ) ?:
			$this->statementValidator->validate( $request->getStatement(), self::SOURCE_STATEMENT ) ?:
				$this->editMetadataValidator->validateEditTags( $request->getEditTags(), self::SOURCE_EDIT_TAGS );
	}

	public function getValidatedStatement(): ?Statement {
		return $this->statementValidator->getValidatedStatement();
	}

}

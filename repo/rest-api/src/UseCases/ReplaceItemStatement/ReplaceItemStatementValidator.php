<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement;

use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class ReplaceItemStatementValidator {

	public const SOURCE_ITEM_ID = 'item ID';
	public const SOURCE_STATEMENT_ID = 'statement ID';
	public const SOURCE_STATEMENT = 'statement';
	public const SOURCE_CHANGED_STATEMENT_ID = 'changed statement ID';
	public const SOURCE_COMMENT = 'comment';
	public const SOURCE_EDIT_TAGS = 'edit tags';

	private $itemIdValidator;
	private $statementIdValidator;
	private $editMetadataValidator;
	private $statementValidator;

	public function __construct(
		ItemIdValidator $itemIdValidator,
		StatementIdValidator $statementIdValidator,
		StatementValidator $statementValidator,
		EditMetadataValidator $editMetadataValidator
	) {
		$this->itemIdValidator = $itemIdValidator;
		$this->statementIdValidator = $statementIdValidator;
		$this->statementValidator = $statementValidator;
		$this->editMetadataValidator = $editMetadataValidator;
	}

	public function validate( ReplaceItemStatementRequest $request ): ?ValidationError {
		return $this->validateItemId( $request->getItemId() ) ?:
			$this->statementIdValidator->validate( $request->getStatementId(), self::SOURCE_STATEMENT_ID ) ?:
				$this->statementValidator->validate( $request->getStatement(), self::SOURCE_STATEMENT ) ?:
					$this->editMetadataValidator->validateEditTags( $request->getEditTags(), self::SOURCE_EDIT_TAGS ) ?:
						$this->editMetadataValidator->validateComment( $request->getComment(), self::SOURCE_COMMENT ) ?:
							$this->validateStatementIdsMatch( $request->getStatementId(), $request->getStatement() );
	}

	public function getValidatedStatement(): ?Statement {
		return $this->statementValidator->getValidatedStatement();
	}

	private function validateItemId( ?string $itemId ): ?ValidationError {
		return $itemId ? $this->itemIdValidator->validate( $itemId, self::SOURCE_ITEM_ID ) : null;
	}

	private function validateStatementIdsMatch( string $statementId, array $statement ): ?ValidationError {
		if ( array_key_exists( 'id', $statement ) && $statementId !== $statement['id'] ) {
			return new ValidationError( '', self::SOURCE_CHANGED_STATEMENT_ID );
		}

		return null;
	}
}

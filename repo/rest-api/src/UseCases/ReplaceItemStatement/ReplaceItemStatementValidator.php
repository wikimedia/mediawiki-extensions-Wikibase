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

	private ItemIdValidator $itemIdValidator;
	private StatementIdValidator $statementIdValidator;
	private EditMetadataValidator $editMetadataValidator;
	private StatementValidator $statementValidator;

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
			$this->statementIdValidator->validate( $request->getStatementId() ) ?:
				$this->statementValidator->validate( $request->getStatement() ) ?:
					$this->editMetadataValidator->validateEditTags( $request->getEditTags() ) ?:
						$this->editMetadataValidator->validateComment( $request->getComment() );
	}

	public function getValidatedStatement(): ?Statement {
		return $this->statementValidator->getValidatedStatement();
	}

	private function validateItemId( ?string $itemId ): ?ValidationError {
		return $itemId ? $this->itemIdValidator->validate( $itemId ) : null;
	}
}

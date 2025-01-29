<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation;

use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class ItemStatementIdRequestValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( ItemStatementIdRequest $request ): void {
		$itemId = $request->getItemId();
		$statementId = $request->getStatementId();

		if ( !$this->assertItemIdEqualToStatementSubjectId( $itemId, $statementId ) ) {
			throw new UseCaseError(
				UseCaseError::ITEM_STATEMENT_ID_MISMATCH,
				'IDs of the Item and the Statement do not match',
				[ UseCaseError::CONTEXT_ITEM_ID => $itemId, UseCaseError::CONTEXT_STATEMENT_ID => $statementId ]
			);
		}
	}

	private function assertItemIdEqualToStatementSubjectId( string $itemId, string $statementId ): bool {
		$statementSubjectId = strtok( $statementId, StatementGuid::SEPARATOR );
		// This check detects mismatches early, whereas a case-sensitive check might not necessarily identify a mismatch.
		// The case-sensitive check will be performed later in the process.
		return strtoupper( $statementSubjectId ) === strtoupper( $itemId );
	}

}

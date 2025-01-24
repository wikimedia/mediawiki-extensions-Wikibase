<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation;

use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class PropertyStatementIdRequestValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PropertyStatementIdRequest $request ): void {
		$propertyId = $request->getPropertyId();
		$statementId = $request->getStatementId();

		if ( !$this->assertPropertyIdEqualToStatementSubjectId( $propertyId, $statementId ) ) {
			throw new UseCaseError(
				UseCaseError::PROPERTY_STATEMENT_ID_MISMATCH,
				'IDs of the Property and the Statement do not match',
				[ UseCaseError::CONTEXT_PROPERTY_ID => $propertyId, UseCaseError::CONTEXT_STATEMENT_ID => $statementId ]
			);
		}
	}

	private function assertPropertyIdEqualToStatementSubjectId( string $propertyId, string $statementId ): bool {
		$statementSubjectId = strtok( $statementId, StatementGuid::SEPARATOR );
		// This check detects mismatches early, whereas a case-sensitive check might not necessarily identify a mismatch.
		// The case-sensitive check will be performed later in the process.
		return strtoupper( $statementSubjectId ) === strtoupper( $propertyId );
	}

}

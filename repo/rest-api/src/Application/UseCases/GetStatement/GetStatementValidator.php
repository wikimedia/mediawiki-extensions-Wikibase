<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetStatement;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\EntityIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetStatementValidator {

	private StatementIdValidator $statementIdValidator;
	private EntityIdValidator $subjectIdValidator;

	public function __construct( StatementIdValidator $statementIdValidator, EntityIdValidator $subjectIdValidator ) {
		$this->statementIdValidator = $statementIdValidator;
		$this->subjectIdValidator = $subjectIdValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function assertValidRequest( GetStatementRequest $statementRequest ): void {
		$validationError = $this->statementIdValidator->validate( $statementRequest->getStatementId() );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_STATEMENT_ID,
				'Not a valid statement ID: ' . $validationError->getContext()[StatementIdValidator::CONTEXT_VALUE]
			);
		}

		$this->validateSubjectId( $statementRequest->getEntityId() );
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateSubjectId( ?string $entityId ): void {
		if ( !isset( $entityId ) ) {
			return;
		}

		$validationError = $this->subjectIdValidator->validate( $entityId );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_STATEMENT_SUBJECT_ID,
				'Not a valid subject ID: ' . $validationError->getContext()[EntityIdValidator::CONTEXT_VALUE]
			);
		}
	}

}

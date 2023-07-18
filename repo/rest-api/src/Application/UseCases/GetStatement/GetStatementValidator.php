<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetStatement;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\RequestedSubjectIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetStatementValidator {

	private StatementIdValidator $statementIdValidator;
	private RequestedSubjectIdValidator $requestedSubjectIdValidator;

	public function __construct( StatementIdValidator $statementIdValidator, RequestedSubjectIdValidator $requestedSubjectIdValidator ) {
		$this->statementIdValidator = $statementIdValidator;
		$this->requestedSubjectIdValidator = $requestedSubjectIdValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function assertValidRequest( GetStatementRequest $statementRequest ): void {
		$subjectId = $statementRequest->getSubjectId();
		$validationError = $this->requestedSubjectIdValidator->validate( $subjectId );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_STATEMENT_SUBJECT_ID,
				'Not a valid subject ID: ' . $validationError->getContext()[RequestedSubjectIdValidator::CONTEXT_VALUE]
			);
		}

		$validationError = $this->statementIdValidator->validate( $statementRequest->getStatementId() );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_STATEMENT_ID,
				'Not a valid statement ID: ' . $validationError->getContext()[StatementIdValidator::CONTEXT_VALUE]
			);
		}
	}

}

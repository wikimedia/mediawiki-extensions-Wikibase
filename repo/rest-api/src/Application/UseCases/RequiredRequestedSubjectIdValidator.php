<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

use LogicException;
use Wikibase\Repo\RestApi\Application\Validation\EntityIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class RequiredRequestedSubjectIdValidator implements RequestedSubjectIdValidator {

	private EntityIdValidator $subjectIdValidator;

	public function __construct( EntityIdValidator $subjectIdValidator ) {
		$this->subjectIdValidator = $subjectIdValidator;
	}

	public function assertValid( ?string $subjectId ): void {
		if ( $subjectId === null ) {
			throw new LogicException( "\$subjectId shouldn't be null" );
		}

		$validationError = $this->subjectIdValidator->validate( $subjectId );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_STATEMENT_SUBJECT_ID,
				"Not a valid subject ID: $subjectId"
			);
		}
	}

}

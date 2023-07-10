<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use LogicException;

/**
 * @license GPL-2.0-or-later
 */
class RequiredRequestedSubjectIdValidator implements RequestedSubjectIdValidator {

	private EntityIdValidator $subjectIdValidator;

	public function __construct( EntityIdValidator $subjectIdValidator ) {
		$this->subjectIdValidator = $subjectIdValidator;
	}

	public function validate( ?string $subjectId ): ?ValidationError {
		if ( $subjectId === null ) {
			throw new LogicException( "\$subjectId shouldn't be null" );
		}

		if ( $this->subjectIdValidator->validate( $subjectId ) ) {
			return new ValidationError( self::CODE_INVALID, [ self::CONTEXT_VALUE => $subjectId ] );
		}

		return null;
	}

}

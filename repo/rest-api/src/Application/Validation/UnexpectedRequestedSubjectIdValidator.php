<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use LogicException;

/**
 * @license GPL-2.0-or-later
 */
class UnexpectedRequestedSubjectIdValidator implements RequestedSubjectIdValidator {

	public function validate( ?string $subjectId ): ?ValidationError {
		if ( $subjectId !== null ) {
			throw new LogicException( '$subjectId should be null' );
		}

		return null;
	}

}

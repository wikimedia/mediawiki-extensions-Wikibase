<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

use LogicException;

/**
 * @license GPL-2.0-or-later
 */
class UnexpectedRequestedSubjectIdValidator implements RequestedSubjectIdValidator {

	public function assertValid( ?string $subjectId ): void {
		if ( $subjectId !== null ) {
			throw new LogicException( '$subjectId should be null' );
		}
	}

}

<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

/**
 * @license GPL-2.0-or-later
 */
interface RequestedSubjectIdValidator {

	/**
	 * @throws UseCaseError
	 */
	public function assertValid( ?string $subjectId ): void;

}

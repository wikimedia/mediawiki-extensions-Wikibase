<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

/**
 * @license GPL-2.0-or-later
 */
interface RequestedSubjectIdValidator {

	public const CODE_INVALID = 'invalid-subject-id';
	public const CONTEXT_VALUE = 'subject-id';

	public function validate( ?string $subjectId ): ?ValidationError;

}

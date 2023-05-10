<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

/**
 * @license GPL-2.0-or-later
 */
interface ItemLabelTextValidator {

	public const CODE_INVALID = 'invalid-label';
	public const CODE_EMPTY = 'label-empty';
	public const CODE_TOO_LONG = 'label-too-long';

	public const CONTEXT_VALUE = 'value';
	public const CONTEXT_LIMIT = 'character-limit';

	public function validate( string $label ): ?ValidationError;

}

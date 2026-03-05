<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Validation;

/**
 * @license GPL-2.0-or-later
 */
class InvalidResult {

	public function __construct(
		public readonly array $errorResponse,
		public readonly string $errorType,
	) {
	}

}

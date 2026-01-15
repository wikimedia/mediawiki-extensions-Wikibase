<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors;

use GraphQL\Error\Error;

/**
 * @license GPL-2.0-or-later
 */
class InvalidSearchCursor extends Error {
	public function __construct() {
		parent::__construct( '"after" does not contain a valid cursor' );
	}

	public function isClientSafe(): bool {
		return true;
	}
}

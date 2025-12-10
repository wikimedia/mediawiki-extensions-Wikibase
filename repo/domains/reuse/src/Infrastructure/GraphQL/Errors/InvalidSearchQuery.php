<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors;

use GraphQL\Error\Error;

/**
 * @license GPL-2.0-or-later
 */
class InvalidSearchQuery extends Error {
	public function __construct( string $reason ) {
		parent::__construct( "Invalid search query: $reason" );
	}

	public function isClientSafe(): bool {
		return true;
	}
}

<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors;

use GraphQL\Error\Error;

/**
 * @license GPL-2.0-or-later
 */
class ItemNotFound extends Error {
	public function __construct( string $itemId ) {
		parent::__construct( "Item \"$itemId\" does not exist." );
	}

	public function isClientSafe(): bool {
		return true;
	}
}

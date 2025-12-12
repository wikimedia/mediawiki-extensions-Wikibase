<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors;

use GraphQL\Error\Error;

/**
 * @license GPL-2.0-or-later
 */
class SearchNotAvailable extends Error {
	public function __construct() {
		parent::__construct( 'Search is not available due to insufficient server configuration' );
	}

	public function isClientSafe(): bool {
		return true;
	}
}

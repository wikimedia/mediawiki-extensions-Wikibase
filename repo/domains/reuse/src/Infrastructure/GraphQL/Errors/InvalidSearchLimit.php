<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors;

use GraphQL\Error\Error;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchRequest;

/**
 * @license GPL-2.0-or-later
 */
class InvalidSearchLimit extends Error {
	public function __construct() {
		parent::__construct( '"first" must not be less than 1 or greater than ' . FacetedItemSearchRequest::MAX_LIMIT );
	}

	public function isClientSafe(): bool {
		return true;
	}
}

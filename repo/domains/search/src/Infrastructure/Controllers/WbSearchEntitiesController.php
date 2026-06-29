<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\Controllers;

use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Api\EntitySearchException;

/**
 * @license GPL-2.0-or-later
 */
interface WbSearchEntitiesController {

	/**
	 * @return TermSearchResult[]
	 * @throws EntitySearchException
	 */
	public function search( WbSearchEntitiesRequest $request ): array;

}

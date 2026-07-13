<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\Controllers;

use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\Repo\Api\ConceptUriSearchHelper;
use Wikibase\Repo\Api\EntitySearchHelper;

/**
 * @license GPL-2.0-or-later
 */
class FallbackEntitySearchHelperController implements PaginatingWbSearchEntitiesController {

	private readonly EntitySearchHelper $searchHelper;

	public function __construct(
		private readonly string $entityType,
		EntitySearchHelper $searchHelper,
		EntitySourceLookup $entitySourceLookup
	) {
		$this->searchHelper = new ConceptUriSearchHelper( $searchHelper, $entitySourceLookup );
	}

	/**
	 * @inheritDoc
	 */
	public function search( WbSearchEntitiesRequest $request ): WbSearchEntitiesResponse {
		// $request->resultLanguage is not used here. The underlying EntitySearchHelper is expected to get the result language from global
		// state instead. Any entity type specific controller should make use of $request->resultLanguage directly. See T423217.
		$results = $this->searchHelper->getRankedSearchResults(
			$request->text,
			$request->searchLanguageCode,
			$this->entityType,
			$request->offset + $request->limit + 1,
			$request->strictLanguage,
			$request->profileContext
		);

		return new WbSearchEntitiesResponse(
			array_slice( $results, $request->offset, $request->limit ),
			count( $results ) > $request->offset + $request->limit
		);
	}

}

<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\Controllers;

use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\Repo\Api\ConceptUriSearchHelper;
use Wikibase\Repo\Api\EntitySearchHelper;

/**
 * @license GPL-2.0-or-later
 */
class FallbackEntitySearchHelperController implements WbSearchEntitiesController {

	private readonly EntitySearchHelper $searchHelper;

	public function __construct(
		private readonly string $entityType,
		EntitySearchHelper $searchHelper,
		EntitySourceLookup $entitySourceLookup
	) {
		$this->searchHelper = new ConceptUriSearchHelper( $searchHelper, $entitySourceLookup );
	}

	public function search( WbSearchEntitiesRequest $request ): array {
		// $request->resultLanguage is not used here. The underlying EntitySearchHelper is expected to get the result language from global
		// state instead. Any entity type specific controller should make use of $request->resultLanguage directly. See T423217.
		return $this->searchHelper->getRankedSearchResults(
			$request->text,
			$request->searchLanguageCode,
			$this->entityType,
			$request->limit,
			$request->strictLanguage,
			$request->profileContext
		);
	}

}

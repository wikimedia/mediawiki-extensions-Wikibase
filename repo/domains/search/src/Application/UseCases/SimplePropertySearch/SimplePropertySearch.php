<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch;

use Wikibase\Repo\Domains\Search\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Search\Domain\Services\PropertySearchEngine;

/**
 * @license GPL-2.0-or-later
 */
class SimplePropertySearch {

	private SimplePropertySearchValidator $validator;
	private PropertySearchEngine $searchEngine;

	public function __construct(
		SimplePropertySearchValidator $validator,
		PropertySearchEngine $searchEngine
	) {
		$this->validator = $validator;
		$this->searchEngine = $searchEngine;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( SimplePropertySearchRequest $propertyRequest ): SimplePropertySearchResponse {
		$this->validator->validate( $propertyRequest );

		$searchTerm = $propertyRequest->getQuery();
		$language = $propertyRequest->getLanguage();

		return new SimplePropertySearchResponse( $this->searchEngine->searchPropertyByLabel( $searchTerm, $language ) );
	}

}

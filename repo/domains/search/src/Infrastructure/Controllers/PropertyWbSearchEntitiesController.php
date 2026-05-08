<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\Controllers;

use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Domains\Search\Application\UseCases\PropertyPrefixSearch\PropertyPrefixSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\PropertyPrefixSearch\PropertyPrefixSearchRequest;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertySearchResult;

/**
 * @license GPL-2.0-or-later
 */
class PropertyWbSearchEntitiesController implements WbSearchEntitiesController {

	public function __construct(
		private readonly PropertyPrefixSearch $propertyPrefixSearch,
		private readonly EntitySourceLookup $entitySourceLookup,
	) {
	}

	public function search( WbSearchEntitiesRequest $request ): array {
		$response = $this->propertyPrefixSearch->execute(
			new PropertyPrefixSearchRequest(
				$request->text,
				$request->searchLanguageCode,
				$request->limit,
				0
			)
		);

		return array_map( $this->convertResult( ... ), iterator_to_array( $response->results ) );
	}

	private function convertResult( PropertySearchResult $result ): TermSearchResult {
		$matchedData = $result->getMatchedData();
		$entityId = $result->getPropertyId();

		$label = $result->getLabel();
		$description = $result->getDescription();

		return new TermSearchResult(
			new Term( $matchedData->getLanguageCode() ?? 'pid', $matchedData->getText() ),
			$matchedData->getType(),
			$entityId,
			$label ? new Term( $label->getLanguageCode(), $label->getText() ) : null,
			$description ? new Term( $description->getLanguageCode(), $description->getText() ) : null,
			[ TermSearchResult::CONCEPTURI_META_DATA_KEY =>
				$this->entitySourceLookup->getEntitySourceById( $entityId )->getConceptBaseUri()
				. wfUrlencode( $entityId->getSerialization() ) ]
		);
	}

}

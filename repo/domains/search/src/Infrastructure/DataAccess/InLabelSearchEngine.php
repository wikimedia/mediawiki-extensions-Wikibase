<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\Description;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResults;
use Wikibase\Repo\Domains\Search\Domain\Model\Label;
use Wikibase\Repo\Domains\Search\Domain\Model\MatchedData;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertySearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertySearchResults;
use Wikibase\Repo\Domains\Search\Domain\Services\ItemSearchEngine;
use Wikibase\Repo\Domains\Search\Domain\Services\PropertySearchEngine;
use Wikibase\Search\Elastic\InLabelSearch;

/**
 * @license GPL-2.0-or-later
 */
class InLabelSearchEngine implements ItemSearchEngine, PropertySearchEngine {
	private const DEFAULT_RESULTS_LIMIT = 10;

	private InLabelSearch $inLabelSearch; // @phan-suppress-current-line PhanUndeclaredTypeProperty

	public function __construct(
		InLabelSearch $inLabelSearch // @phan-suppress-current-line PhanUndeclaredTypeParameter
	) {
		$this->inLabelSearch = $inLabelSearch;
	}

	public function searchItemByLabel( string $searchTerm, string $languageCode ): ItemSearchResults {
		return new ItemSearchResults(
			...array_map(
				$this->convertResult( ItemSearchResult::class ),
				// @phan-suppress-next-line PhanUndeclaredClassMethod
				$this->inLabelSearch->search( $searchTerm, $languageCode, Item::ENTITY_TYPE, self::DEFAULT_RESULTS_LIMIT )
			)
		);
	}

	public function searchPropertyByLabel( string $searchTerm, string $languageCode ): PropertySearchResults {
		return new PropertySearchResults(
			...array_map(
				$this->convertResult( PropertySearchResult::class ),
				// @phan-suppress-next-line PhanUndeclaredClassMethod
				$this->inLabelSearch->search( $searchTerm, $languageCode, Property::ENTITY_TYPE, self::DEFAULT_RESULTS_LIMIT )
			)
		);
	}

	private function convertResult( string $resultClass ): callable {
		return fn( TermSearchResult $result ) => new $resultClass(
			$result->getEntityId(),
			$result->getDisplayLabel()
				? new Label( $result->getDisplayLabel()->getLanguageCode(), $result->getDisplayLabel()->getText() )
				: null,
			$result->getDisplayDescription() ?
				new Description( $result->getDisplayDescription()->getLanguageCode(), $result->getDisplayDescription()->getText() )
				: null,
			new MatchedData(
				$result->getMatchedTermType(),
				$result->getMatchedTerm()->getLanguageCode(),
				$result->getMatchedTerm()->getText()
			)
		);
	}
}

<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\Description;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResults;
use Wikibase\Repo\Domains\Search\Domain\Model\Label;
use Wikibase\Repo\Domains\Search\Domain\Model\MatchedData;
use Wikibase\Repo\Domains\Search\Domain\Services\ItemSearchEngine;
use Wikibase\Search\Elastic\InLabelSearch;

/**
 * @license GPL-2.0-or-later
 */
class InLabelItemSearchEngine implements ItemSearchEngine {
	private const RESULTS_LIMIT = 5;

	private InLabelSearch $inLabelSearch; // @phan-suppress-current-line PhanUndeclaredTypeProperty

	public function __construct(
		InLabelSearch $inLabelSearch // @phan-suppress-current-line PhanUndeclaredTypeParameter
	) {
		$this->inLabelSearch = $inLabelSearch;
	}

	public function searchItemByLabel( string $searchTerm, string $languageCode ): ItemSearchResults {
		return new ItemSearchResults(
			...array_map(
				fn( TermSearchResult $result ) => new ItemSearchResult(
					new ItemId( $result->getEntityId()->getSerialization() ),

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
				),
				// @phan-suppress-next-line PhanUndeclaredClassMethod
				$this->inLabelSearch->search( $searchTerm, $languageCode, Item::ENTITY_TYPE, self::RESULTS_LIMIT )
			)
		);
	}

}

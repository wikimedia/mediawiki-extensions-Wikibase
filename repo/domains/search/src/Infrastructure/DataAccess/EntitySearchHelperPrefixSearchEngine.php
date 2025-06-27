<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\DataAccess;

use MediaWiki\Languages\LanguageFactory;
use MediaWiki\Request\WebRequest;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\Description;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResults;
use Wikibase\Repo\Domains\Search\Domain\Model\Label;
use Wikibase\Repo\Domains\Search\Domain\Model\MatchedData;
use Wikibase\Repo\Domains\Search\Domain\Services\ItemPrefixSearchEngine;
use Wikibase\Search\Elastic\EntitySearchHelperFactory;

/**
 * @license GPL-2.0-or-later
 */
class EntitySearchHelperPrefixSearchEngine implements ItemPrefixSearchEngine {
	public function __construct(
		// @phan-suppress-next-line PhanUndeclaredTypeParameter, PhanUndeclaredTypeProperty WikibaseCirrusSearch is ok here
		private EntitySearchHelperFactory $searchHelperFactory,
		private LanguageFactory $languageFactory,
		private WebRequest $request
	) {
	}

	public function suggestItems( string $searchTerm, string $languageCode, int $limit, int $offset ): ItemSearchResults {
		$results = array_slice(
			// @phan-suppress-next-line PhanUndeclaredClassMethod
			$this->searchHelperFactory->newItemSearchForResultLanguage(
				$this->request,
				$this->languageFactory->getLanguage( $languageCode )
			)->getRankedSearchResults(
				$searchTerm,
				$languageCode,
				Item::ENTITY_TYPE,
				$limit + $offset + 1,
				false,
				null
			),
			$offset,
			$limit
		);

		return new ItemSearchResults( ...array_map( $this->convertResult( ... ), $results ) );
	}

	private function convertResult( TermSearchResult $result ): ItemSearchResult {
		return new ItemSearchResult(
			new ItemId( $result->getEntityId()->getSerialization() ),
			$result->getDisplayLabel()
				? new Label( $result->getDisplayLabel()->getLanguageCode(), $result->getDisplayLabel()->getText() )
				: null,
			$result->getDisplayDescription() ?
				new Description( $result->getDisplayDescription()->getLanguageCode(), $result->getDisplayDescription()->getText() )
				: null,
			new MatchedData(
				$result->getMatchedTermType(),
				$result->getMatchedTerm()->getLanguageCode() === 'qid' ?
					null :
					$result->getMatchedTerm()->getLanguageCode(),
				$result->getMatchedTerm()->getText()
			)
		);
	}
}

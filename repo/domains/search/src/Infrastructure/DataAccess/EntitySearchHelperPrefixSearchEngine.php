<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\DataAccess;

use MediaWiki\Languages\LanguageFactory;
use MediaWiki\Request\WebRequest;
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
use Wikibase\Repo\Domains\Search\Domain\Services\ItemPrefixSearchEngine;
use Wikibase\Repo\Domains\Search\Domain\Services\PropertyPrefixSearchEngine;
use Wikibase\Search\Elastic\EntitySearchHelperFactory;

/**
 * @license GPL-2.0-or-later
 */
class EntitySearchHelperPrefixSearchEngine implements ItemPrefixSearchEngine, PropertyPrefixSearchEngine {
	public function __construct(
		// @phan-suppress-next-line PhanUndeclaredTypeParameter, PhanUndeclaredTypeProperty WikibaseCirrusSearch is ok here
		private EntitySearchHelperFactory $searchHelperFactory,
		private LanguageFactory $languageFactory,
		private WebRequest $request
	) {
	}

	public function suggestItems( string $searchTerm, string $languageCode, int $limit, int $offset ): ItemSearchResults {
		return new ItemSearchResults( ...array_map(
			$this->convertResult( ItemSearchResult::class ),
			$this->suggestEntities( Item::ENTITY_TYPE, $searchTerm, $languageCode, $limit, $offset )
		) );
	}

	public function suggestProperties( string $searchTerm, string $languageCode, int $limit, int $offset ): PropertySearchResults {
		return new PropertySearchResults( ...array_map(
			$this->convertResult( PropertySearchResult::class ),
			$this->suggestEntities( Property::ENTITY_TYPE, $searchTerm, $languageCode, $limit, $offset )
		) );
	}

	private function suggestEntities( string $entityType, string $searchTerm, string $languageCode, int $limit, int $offset ): array {
		return array_slice(
			// @phan-suppress-next-line PhanUndeclaredClassMethod
			$this->searchHelperFactory->newItemPropertySearchHelper(
				$this->request,
				$this->languageFactory->getLanguage( $languageCode )
			)->getRankedSearchResults(
				$searchTerm,
				$languageCode,
				$entityType,
				$limit + $offset + 1,
				false,
				null
			),
			$offset,
			$limit
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
				$result->getMatchedTerm()->getLanguageCode() === 'qid' ?
					null :
					$result->getMatchedTerm()->getLanguageCode(),
				$result->getMatchedTerm()->getText()
			)
		);
	}
}

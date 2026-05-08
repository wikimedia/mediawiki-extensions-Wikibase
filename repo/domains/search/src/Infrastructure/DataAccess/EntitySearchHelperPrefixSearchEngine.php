<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\DataAccess;

use MediaWiki\Language\LanguageFactory;
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

/**
 * @license GPL-2.0-or-later
 */
class EntitySearchHelperPrefixSearchEngine implements ItemPrefixSearchEngine, PropertyPrefixSearchEngine {
	public function __construct(
		private EntitySearchHelperFactory $searchHelperFactory,
		private LanguageFactory $languageFactory,
		private WebRequest $request,
		private array $searchProfiles,
	) {
	}

	public function suggestItems(
		string $searchTerm,
		string $languageCode,
		int $limit,
		int $offset,
		bool $disableLanguageFallback,
		?string $resultLanguageCode,
		?string $profile
	): ItemSearchResults {
		$profileName = $profile ?? array_key_first( $this->searchProfiles );
		// @phan-suppress-next-line PhanTypeMismatchDimFetchNullable searchProfiles always has at least one entry
		$profileContext = $this->searchProfiles[$profileName] ?? null;

		return new ItemSearchResults( ...array_map(
			$this->convertResult( ItemSearchResult::class ),
			$this->suggestEntities(
				Item::ENTITY_TYPE,
				$searchTerm,
				$languageCode,
				$limit,
				$offset,
				$disableLanguageFallback,
				$resultLanguageCode,
				$profileContext
			)
		) );
	}

	public function suggestProperties( string $searchTerm, string $languageCode, int $limit, int $offset ): PropertySearchResults {
		return new PropertySearchResults( ...array_map(
			$this->convertResult( PropertySearchResult::class ),
			$this->suggestEntities( Property::ENTITY_TYPE, $searchTerm, $languageCode, $limit, $offset )
		) );
	}

	private function suggestEntities(
		string $entityType,
		string $searchTerm,
		string $languageCode,
		int $limit,
		int $offset,
		bool $disableLanguageFallback = false,
		?string $resultLanguageCode = null,
		?string $profileContext = null
	): array {
		return array_slice(
			$this->searchHelperFactory->newEntitySearchHelper(
				$entityType,
				$this->languageFactory->getLanguage( $resultLanguageCode ?? $languageCode ),
				$this->request
			)->getRankedSearchResults(
				$searchTerm,
				$languageCode,
				$entityType,
				$limit + $offset + 1,
				$disableLanguageFallback,
				$profileContext
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

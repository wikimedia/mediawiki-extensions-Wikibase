<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\DataAccess;

use MediaWiki\Language\LanguageFactory;
use MediaWiki\Request\WebRequest;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Api\PropertyDataTypeSearchHelper;
use Wikibase\Repo\Domains\Search\Domain\Model\Description;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResults;
use Wikibase\Repo\Domains\Search\Domain\Model\Label;
use Wikibase\Repo\Domains\Search\Domain\Model\MatchedData;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertyPrefixSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertyPrefixSearchResults;
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

	/**
	 * @inheritDoc
	 */
	public function suggestItems(
		string $searchTerm,
		string $languageCode,
		int $limit,
		int $offset,
		bool $disableLanguageFallback,
		string $resultLanguageCode,
		?string $profile
	): ItemSearchResults {
		$profileName = $profile ?? array_key_first( $this->searchProfiles );
		// @phan-suppress-next-line PhanTypeMismatchDimFetchNullable searchProfiles always has at least one entry
		$profileContext = $this->searchProfiles[$profileName] ?? null;

		[ $results, $hasMore ] = $this->suggestEntities(
			Item::ENTITY_TYPE,
			$searchTerm,
			$languageCode,
			$limit,
			$offset,
			$disableLanguageFallback,
			$resultLanguageCode,
			$profileContext
		);

		return ItemSearchResults::withHasMore( $hasMore, ...array_map(
			$this->convertResult( ItemSearchResult::class ),
			$results
		) );
	}

	/**
	 * @inheritDoc
	 */
	public function suggestProperties(
		string $searchTerm,
		string $languageCode,
		int $limit,
		int $offset,
		bool $disableLanguageFallback,
		string $resultLanguageCode
	): PropertyPrefixSearchResults {
		[ $results, $hasMore ] = $this->suggestEntities(
			Property::ENTITY_TYPE,
			$searchTerm,
			$languageCode,
			$limit,
			$offset,
			$disableLanguageFallback,
			$resultLanguageCode
		);

		return PropertyPrefixSearchResults::withHasMore( $hasMore, ...array_map(
			$this->convertPropertyResult( ... ),
			$results
		) );
	}

	private function convertPropertyResult( TermSearchResult $result ): PropertyPrefixSearchResult {
		/** @var PropertyId $propertyId */
		$propertyId = $result->getEntityId();
		'@phan-var PropertyId $propertyId';
		return new PropertyPrefixSearchResult(
			$propertyId,
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
			),
			$result->getMetaData()[PropertyDataTypeSearchHelper::DATATYPE_META_DATA_KEY],
		);
	}

	/**
	 * @return array{0: TermSearchResult[], 1: bool} the requested page of results and whether more exist beyond it
	 */
	private function suggestEntities(
		string $entityType,
		string $searchTerm,
		string $languageCode,
		int $limit,
		int $offset,
		bool $disableLanguageFallback,
		string $resultLanguageCode,
		?string $profileContext = null
	): array {
		$results = $this->searchHelperFactory->newEntitySearchHelper(
			$entityType,
			$this->languageFactory->getLanguage( $resultLanguageCode ),
			$this->request
		)->getRankedSearchResults(
			$searchTerm,
			$languageCode,
			$entityType,
			$limit + $offset + 1,
			$disableLanguageFallback,
			$profileContext
		);

		return [
			array_slice( $results, $offset, $limit ),
			count( $results ) > $offset + $limit,
		];
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

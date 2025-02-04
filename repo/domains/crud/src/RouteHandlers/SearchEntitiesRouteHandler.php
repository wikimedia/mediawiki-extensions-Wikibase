<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\RouteHandlers;

use ISearchResultSet;
use MediaWiki\Languages\LanguageFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use SearchEngineFactory;
use SearchResult;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Search\Elastic\EntityResult;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class SearchEntitiesRouteHandler extends SimpleHandler {

	private const ENTITY_TYPE_PATH_PARAM = 'entity_type';
	private const SEARCH_QUERY_PARAM = 'search';
	// This is not actually used in the code here anywhere. MediaWiki picks up "uselang" on its own.
	private const LANGUAGE_QUERY_PARAM = 'uselang';

	private const ENTITY_TYPE_MAP = [
		'items' => Item::ENTITY_TYPE,
		'properties' => Property::ENTITY_TYPE,
	];
	private const RESULTS_LIMIT = 5;

	private SearchEngineFactory $searchEngineFactory;
	private EntityNamespaceLookup $entityNamespaceLookup;
	private FallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory;
	private LanguageFactory $languageFactory;
	private EntityIdParser $entityIdParser;

	public function __construct(
		SearchEngineFactory $searchEngineFactory,
		EntityNamespaceLookup $entityNamespaceLookup,
		FallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory,
		LanguageFactory $languageFactory,
		EntityIdParser $entityIdParser
	) {
		$this->searchEngineFactory = $searchEngineFactory;
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->labelDescriptionLookupFactory = $labelDescriptionLookupFactory;
		$this->languageFactory = $languageFactory;
		$this->entityIdParser = $entityIdParser;
	}

	public static function factory(): Handler {
		$mediaWikiServices = MediaWikiServices::getInstance();

		return new self(
			$mediaWikiServices->getSearchEngineFactory(),
			WikibaseRepo::getEntityNamespaceLookup(),
			WikibaseRepo::getFallbackLabelDescriptionLookupFactory(),
			$mediaWikiServices->getLanguageFactory(),
			WikibaseRepo::getEntityIdParser()
		);
	}

	public function getParamSettings(): array {
		return [
			self::ENTITY_TYPE_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => array_keys( self::ENTITY_TYPE_MAP ),
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_ISMULTI => false,
			],
			self::SEARCH_QUERY_PARAM => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_ISMULTI => false,
			],
			self::LANGUAGE_QUERY_PARAM => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_ISMULTI => false,
			],
		];
	}

	public function run( string $entityType ): Response {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'WikibaseCirrusSearch' ) ) {
			throw new HttpException( 'This endpoint does not work because WikibaseCirrusSearch is not installed.' );
		}

		$searchTerm = $this->getValidatedParams()[self::SEARCH_QUERY_PARAM];
		$results = $this->fullTextSearch( $entityType, $searchTerm );

		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setBody( new StringStream(
			json_encode( [ 'results' => $results ], JSON_UNESCAPED_SLASHES )
		) );

		return $httpResponse;
	}

	private function fullTextSearch( string $entityType, string $searchTerm ): array {
		$searchEngine = $this->searchEngineFactory->create();
		$searchEngine->setNamespaces( [ $this->entityNamespaceLookup->getEntityNamespace( self::ENTITY_TYPE_MAP[$entityType] ) ] );
		$searchEngine->setLimitOffset( self::RESULTS_LIMIT );
		$resultSet = $searchEngine->searchText( $searchTerm )->getValue();
		if ( !( $resultSet instanceof ISearchResultSet ) ) {
			return [];
		}

		// Not all search results are EntityResult instances, e.g. entities matched using haswbstatement are ArrayCirrusSearchResult objects
		// which don't contain the label and description, so we need to look them up.
		$labelDescriptionLookup = $this->labelDescriptionLookupFactory->newLabelDescriptionLookup(
			$this->languageFactory->getLanguage( $this->getValidatedParams()[self::LANGUAGE_QUERY_PARAM] ),
			array_map(
				fn( SearchResult $result ) => $this->entityIdParser->parse( $result->getTitle()->getText() ),
				array_filter(
					$resultSet->extractResults(),
					// @phan-suppress-next-line PhanUndeclaredClassInstanceof - phan does not know about WikibaseCirrusSearch
					fn( SearchResult $result ) => !( $result instanceof EntityResult )
				)
			),
			[ TermTypes::TYPE_LABEL, TermTypes::TYPE_DESCRIPTION ]
		);

		return array_map(
			function ( SearchResult $result ) use ( $labelDescriptionLookup ) {
				// @phan-suppress-next-line PhanUndeclaredClassInstanceof - phan does not know about WikibaseCirrusSearch
				if ( $result instanceof EntityResult ) {
					return [
						// @phan-suppress-next-line PhanUndeclaredClassMethod - phan does not know about WikibaseCirrusSearch
						'id' => $result->getTitle()->getText(),
						// @phan-suppress-next-line PhanUndeclaredClassMethod - phan does not know about WikibaseCirrusSearch
						'label' => $result->getLabelData()['value'] ?? null,
						// @phan-suppress-next-line PhanUndeclaredClassMethod - phan does not know about WikibaseCirrusSearch
						'description' => $result->getDescriptionData()['value'] ?: null,
					];
				}

				$id = $this->entityIdParser->parse( $result->getTitle()->getText() );
				$label = $labelDescriptionLookup->getLabel( $id );
				$description = $labelDescriptionLookup->getDescription( $id );

				return [
					'id' => "$id",
					'label' => $label ? $label->getText() : null,
					'description' => $description ? $description->getText() : null,
				];
			},
			$resultSet->extractResults()
		);
	}

}

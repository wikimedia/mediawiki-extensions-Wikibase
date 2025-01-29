<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class SuggestEntitiesRouteHandler extends SimpleHandler {

	private const ENTITY_TYPE_PATH_PARAM = 'entity_type';
	private const SEARCH_QUERY_PARAM = 'search';
	// We only actively use "uselang" in this class to set the language to search in, but MediaWiki picks it up automatically to set the
	// result language. If we used a different query param name, the results would not automatically be shown in the language that was
	// searched in.
	private const LANGUAGE_QUERY_PARAM = 'uselang';

	private const ENTITY_TYPE_MAP = [
		'items' => Item::ENTITY_TYPE,
		'properties' => Property::ENTITY_TYPE,
	];
	private const RESULTS_LIMIT = 5;

	private EntitySearchHelper $entitySearch;

	public function __construct(
		EntitySearchHelper $entitySearch
	) {
		$this->entitySearch = $entitySearch;
	}

	public static function factory(): Handler {
		return new self(
			WikibaseRepo::getEntitySearchHelper(),
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
		$language = $this->getValidatedParams()[self::LANGUAGE_QUERY_PARAM];
		$results = $this->prefixSearch( $entityType, $searchTerm, $language );

		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setBody( new StringStream(
			json_encode( [ 'results' => $results ], JSON_UNESCAPED_SLASHES )
		) );

		return $httpResponse;
	}

	private function prefixSearch( string $entityType, string $searchTerm, string $language ): array {
		return array_map(
			fn( TermSearchResult $searchResult ) => [
				'id' => $searchResult->getEntityId()->getSerialization(),
				'label' => $searchResult->getDisplayLabel()->getText(),
				'description' => $searchResult->getDisplayDescription() ? $searchResult->getDisplayDescription()->getText() : null,
				'match' => [
					'type' => $searchResult->getMatchedTermType(),
					'language' => $searchResult->getMatchedTerm()->getLanguageCode(),
					'text' => $searchResult->getMatchedTerm()->getText(),
				],
			],
			array_values( $this->entitySearch->getRankedSearchResults(
				$searchTerm,
				$language,
				self::ENTITY_TYPE_MAP[$entityType],
				self::RESULTS_LIMIT,
				true,
				null
			) )
		);
	}
}

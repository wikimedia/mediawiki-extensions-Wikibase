<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\RouteHandlers;

use Exception;
use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearchRequest;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResults;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\MediaWikiSearchEngine;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\SqlTermStoreSearchEngine;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class SimpleItemSearchRouteHandler extends SimpleHandler {

	private const SEARCH_QUERY_PARAM = 'q';
	private const LANGUAGE_QUERY_PARAM = 'language';

	private SimpleItemSearch $useCase;

	public function __construct( SimpleItemSearch $useCase ) {
		$this->useCase = $useCase;
	}

	public static function factory(): Handler {
		global $wgSearchType;

		$searchEngine = $wgSearchType === 'CirrusSearch'
			? new MediaWikiSearchEngine(
				MediaWikiServices::getInstance()->getSearchEngineFactory()->create(),
				WikibaseRepo::getEntityNamespaceLookup(),
				RequestContext::getMain()
			)
			: new SqlTermStoreSearchEngine(
				WikibaseRepo::getMatchingTermsLookupFactory()
					->getLookupForSource( WikibaseRepo::getLocalEntitySource() ),
				WikibaseRepo::getTermLookup()
			);

		return new self( new SimpleItemSearch( $searchEngine ) );
	}

	public function run(): Response {
		try {
			$useCaseResponse = $this->useCase->execute( new SimpleItemSearchRequest(
				$this->getValidatedParams()[self::SEARCH_QUERY_PARAM],
				$this->getValidatedParams()[self::LANGUAGE_QUERY_PARAM]
			) );
		} catch ( Exception $e ) {
			throw new HttpException( $e->getMessage() );
		}

		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setBody(
			new StringStream(
				json_encode( [ 'results' => $this->formatResults( $useCaseResponse->getResults() ) ], JSON_UNESCAPED_SLASHES )
			)
		);

		return $httpResponse;
	}

	private function formatResults( ItemSearchResults $results ): array {
		return array_map(
			fn( ItemSearchResult $result ) => [
				'id' => $result->getItemId()->getSerialization(),
				'label' => $result->getLabel(),
				'description' => $result->getDescription(),
			],
			iterator_to_array( $results )
		);
	}

	public function getParamSettings(): array {
		return [
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

}

<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Wikibase\Repo\Domains\Search\Application\UseCases\PropertyPrefixSearch\PropertyPrefixSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\PropertyPrefixSearch\PropertyPrefixSearchRequest;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertySearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertySearchResults;
use Wikibase\Repo\Domains\Search\WbSearch;
use Wikibase\Repo\RestApi\Middleware\MiddlewareHandler;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class PropertyPrefixSearchRouteHandler extends SimpleHandler {

	use CirrusSearchEnabledTrait;

	private const SEARCH_QUERY_PARAM = 'q';
	private const LANGUAGE_QUERY_PARAM = 'language';
	private const LIMIT_QUERY_PARAM = 'limit';
	private const OFFSET_QUERY_PARAM = 'offset';

	public function __construct(
		private PropertyPrefixSearch $useCase,
		private MiddlewareHandler $middlewareHandler,
		private ResponseFactory $responseFactory
	) {
	}

	public static function factory(): Handler {
		return self::isCirrusSearchEnabled()
			? new self(
				WbSearch::getPropertyPrefixSearch(),
				WbSearch::getMiddlewareHandler(),
				new ResponseFactory()
			) : new RestfulSearchNotAvailableRouteHandler();
	}

	public function run(): Response {
		return $this->middlewareHandler->run( $this, $this->runUseCase( ... ) );
	}

	public function runUseCase(): Response {
		$useCaseResponse = $this->useCase->execute( new PropertyPrefixSearchRequest(
			$this->getValidatedParams()[self::SEARCH_QUERY_PARAM],
			$this->getValidatedParams()[self::LANGUAGE_QUERY_PARAM],
			$this->getValidatedParams()[self::LIMIT_QUERY_PARAM] ?? PropertyPrefixSearchRequest::DEFAULT_LIMIT,
			$this->getValidatedParams()[self::OFFSET_QUERY_PARAM] ?? PropertyPrefixSearchRequest::DEFAULT_OFFSET
		) );

		return $this->responseFactory->newSuccessResponse(
			[ 'results' => $this->formatResults( $useCaseResponse->results ) ]
		);
	}

	private function formatResults( PropertySearchResults $results ): array {
		return array_map(
			fn( PropertySearchResult $result ) => [
				'id' => $result->getPropertyId()->getSerialization(),
				'display-label' => $result->getLabel() ? [
					'language' => $result->getLabel()->getLanguageCode(),
					'value' => $result->getLabel()->getText(),
				] : null,
				'description' => $result->getDescription() ? [
					'language' => $result->getDescription()->getLanguageCode(),
					'value' => $result->getDescription()->getText(),
				] : null,
				'match' => array_filter( [
					'type' => $result->getMatchedData()->getType(),
					'language' => $result->getMatchedData()->getLanguageCode(),
					'text' => $result->getMatchedData()->getText(),
				] ),
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
			self::LIMIT_QUERY_PARAM => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_ISMULTI => false,
			],
			self::OFFSET_QUERY_PARAM => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_ISMULTI => false,
			],
		];
	}

}

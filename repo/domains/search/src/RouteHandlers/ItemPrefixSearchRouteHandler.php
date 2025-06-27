<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\RouteHandlers;

use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\Domains\Search\Application\UseCases\ItemPrefixSearch\ItemPrefixSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\ItemPrefixSearch\ItemPrefixSearchRequest;
use Wikibase\Repo\Domains\Search\Application\UseCases\ItemPrefixSearch\ItemPrefixSearchResponse;
use Wikibase\Repo\Domains\Search\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResults;
use Wikibase\Repo\RestApi\Middleware\MiddlewareHandler;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class ItemPrefixSearchRouteHandler extends SimpleHandler {

	private const SEARCH_QUERY_PARAM = 'q';
	private const LANGUAGE_QUERY_PARAM = 'language';
	private const LIMIT_QUERY_PARAM = 'limit';
	private const OFFSET_QUERY_PARAM = 'offset';

	public function __construct(
		private ItemPrefixSearch $useCase,
		private MiddlewareHandler $middlewareHandler
	) {
	}

	public function run(): Response {
		return $this->middlewareHandler->run( $this, $this->runUseCase( ... ) );
	}

	public function runUseCase(): Response {
		try {
			$useCaseResponse = $this->useCase->execute( new ItemPrefixSearchRequest(
				$this->getValidatedParams()[self::SEARCH_QUERY_PARAM],
				$this->getValidatedParams()[self::LANGUAGE_QUERY_PARAM],
				$this->getValidatedParams()[self::LIMIT_QUERY_PARAM] ?? ItemPrefixSearchRequest::DEFAULT_LIMIT,
				$this->getValidatedParams()[self::OFFSET_QUERY_PARAM] ?? ItemPrefixSearchRequest::DEFAULT_OFFSET
			) );
		} catch ( UseCaseError $e ) {
			return $this->newErrorResponse( $e->getErrorCode(), $e->getErrorMessage(), $e->getErrorContext() );
		}

		return $this->newSuccessResponse( $useCaseResponse );
	}

	private function newSuccessResponse( ItemPrefixSearchResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setBody(
			new StringStream(
				json_encode( [ 'results' => $this->formatResults( $useCaseResponse->getResults() ) ], JSON_UNESCAPED_SLASHES )
			)
		);

		return $httpResponse;
	}

	private function newErrorResponse( string $code, string $message, ?array $context = null ): Response {
		$httpResponse = new Response();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Content-Language', 'en' );
		$httpResponse->setStatus( ErrorResponseToHttpStatus::lookup( $code ) );
		$httpResponse->setBody( new StringStream( json_encode(
			// use array_filter to remove 'context' from array if $context is NULL
			array_filter( [ 'code' => $code, 'message' => $message, 'context' => $context ] ),
			JSON_UNESCAPED_SLASHES
		) ) );

		return $httpResponse;
	}

	private function formatResults( ItemSearchResults $results ): array {
		return array_map(
			fn( ItemSearchResult $result ) => [
				'id' => $result->getItemId()->getSerialization(),
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

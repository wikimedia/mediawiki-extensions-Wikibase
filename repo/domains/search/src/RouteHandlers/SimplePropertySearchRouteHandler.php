<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\RouteHandlers;

use Exception;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch\SimplePropertySearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch\SimplePropertySearchRequest;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch\SimplePropertySearchResponse;
use Wikibase\Repo\Domains\Search\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertySearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertySearchResults;
use Wikibase\Repo\Domains\Search\WbSearch;
use Wikibase\Repo\RestApi\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\Middleware\UserAgentCheckMiddleware;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class SimplePropertySearchRouteHandler extends SimpleHandler {

	private const SEARCH_QUERY_PARAM = 'q';
	private const LANGUAGE_QUERY_PARAM = 'language';

	private SimplePropertySearch $useCase;
	private MiddlewareHandler $middlewareHandler;

	public function __construct( SimplePropertySearch $useCase, MiddlewareHandler $middlewareHandler ) {
		$this->useCase = $useCase;
		$this->middlewareHandler = $middlewareHandler;
	}

	public static function factory(): Handler {
		return new self(
			WbSearch::getSimplePropertySearch(),
			new MiddlewareHandler( [
				WbSearch::getUnexpectedErrorHandlerMiddleware(),
				new UserAgentCheckMiddleware(),
			] )
		);
	}

	public function run(): Response {
		return $this->middlewareHandler->run( $this, fn() => $this->runUseCase() );
	}

	public function runUseCase(): Response {
		try {
			$useCaseResponse = $this->useCase->execute( new SimplePropertySearchRequest(
				$this->getValidatedParams()[self::SEARCH_QUERY_PARAM],
				$this->getValidatedParams()[self::LANGUAGE_QUERY_PARAM]
			) );
		} catch ( UseCaseError $e ) {
			return $this->newErrorResponse( $e->getErrorCode(), $e->getErrorMessage(), $e->getErrorContext() );
		} catch ( Exception $e ) {
			throw new HttpException( $e->getMessage() );
		}

		return $this->newSuccessResponse( $useCaseResponse );
	}

	private function newSuccessResponse( SimplePropertySearchResponse $useCaseResponse ): Response {
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
				'match' => [
					'type' => $result->getMatchedData()->getType(),
					'language' => $result->getMatchedData()->getLanguageCode(),
					'text' => $result->getMatchedData()->getText(),
				],
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

<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use MediaWiki\Rest\Validator\BodyValidator;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescription;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescriptionRequest;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescriptionResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\UserAgentCheckMiddleware;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class SetItemDescriptionRouteHandler extends SimpleHandler {
	private const ITEM_ID_PATH_PARAM = 'item_id';
	private const LANGUAGE_CODE_PATH_PARAM = 'language_code';

	private SetItemDescription $useCase;
	private ResponseFactory $responseFactory;
	private MiddlewareHandler $middlewareHandler;

	public function __construct(
		SetItemDescription $useCase,
		ResponseFactory $responseFactory,
		MiddlewareHandler $middlewareHandler
	) {
		$this->useCase = $useCase;
		$this->responseFactory = $responseFactory;
		$this->middlewareHandler = $middlewareHandler;
	}

	public static function factory(): self {
		return new self(
			WbRestApi::getSetItemDescription(),
			new ResponseFactory(),
			new MiddlewareHandler( [
				WbRestApi::getUnexpectedErrorHandlerMiddleware(),
				new UserAgentCheckMiddleware(),
			] )
		);
	}

	/**
	 * @param mixed ...$args
	 */
	public function run( ...$args ): Response {
		return $this->middlewareHandler->run( $this, [ $this, 'runUseCase' ], $args );
	}

	public function runUseCase( string $itemId, string $languageCode ): Response {
		$jsonBody = $this->getValidatedBody();

		try {
			return $this->newSuccessHttpResponse(
				$this->useCase->execute( new SetItemDescriptionRequest(
					$itemId,
					$languageCode,
					$jsonBody['description'],
					$jsonBody['tags'] ?? [],
					$jsonBody['bot'] ?? false,
					$jsonBody['comment'] ?? null
				) )
			);
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getBodyValidator( $contentType ): BodyValidator {
		return new TypeValidatingJsonBodyValidator( [] );
	}

	public function getParamSettings(): array {
		return [
			self::ITEM_ID_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			self::LANGUAGE_CODE_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}

	private function newSuccessHttpResponse( SetItemDescriptionResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setStatus( $useCaseResponse->wasReplaced() ? 200 : 201 );
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'ETag', "\"{$useCaseResponse->getRevisionId()}\"" );
		$httpResponse->setHeader(
			'Last-Modified',
			wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() )
		);
		$httpResponse->setBody(
			new StringStream(
				json_encode(
					$useCaseResponse->getDescription()->getText()
				)
			)
		);

		return $httpResponse;
	}
}

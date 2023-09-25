<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabel\GetPropertyLabel;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabel\GetPropertyLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabel\GetPropertyLabelResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;
use function json_encode;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyLabelRouteHandler extends SimpleHandler {

	private const PROPERTY_ID_PATH_PARAM = 'property_id';
	private const LANGUAGE_CODE_PATH_PARAM = 'language_code';

	private GetPropertyLabel $useCase;
	private ResponseFactory $responseFactory;

	public function __construct(
		GetPropertyLabel $useCase,
		ResponseFactory $responseFactory
	) {
		$this->useCase = $useCase;
		$this->responseFactory = $responseFactory;
	}

	public static function factory(): self {
		return new self(
			WbRestApi::getGetPropertyLabel(),
			new ResponseFactory()
		);
	}

	public function run( string $propertyId, string $languageCode ): Response {
		try {
			$useCaseResponse = $this->useCase->execute( new GetPropertyLabelRequest( $propertyId, $languageCode ) );
			return $this->newSuccessResponse( $useCaseResponse );
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		}
	}

	public function getParamSettings(): array {
		return [
			self::PROPERTY_ID_PATH_PARAM => [
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

	private function newSuccessResponse( GetPropertyLabelResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() ) );
		$httpResponse->setHeader( 'ETag', "\"{$useCaseResponse->getRevisionId()}\"" );
		$httpResponse->setBody(
			new StringStream( json_encode( $useCaseResponse->getLabel()->getText() ) )
		);

		return $httpResponse;
	}

}

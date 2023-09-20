<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliases\GetPropertyAliases;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliases\GetPropertyAliasesRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyAliasesRouteHandler extends SimpleHandler {

	private const PROPERTY_ID_PATH_PARAM = 'property_id';

	private GetPropertyAliases $useCase;
	private AliasesSerializer $aliasesSerializer;
	private ResponseFactory $responseFactory;

	public function __construct(
		GetPropertyAliases $useCase,
		AliasesSerializer $aliasesSerializer,
		ResponseFactory $responseFactory
	) {
		$this->useCase = $useCase;
		$this->aliasesSerializer = $aliasesSerializer;
		$this->responseFactory = $responseFactory;
	}

	public static function factory(): self {
		return new self(
			WbRestApi::getGetPropertyAliases(),
			new AliasesSerializer(),
			new ResponseFactory()
		);
	}

	public function run( string $propertyId ): Response {
		try {
			$useCaseResponse = $this->useCase->execute( new GetPropertyAliasesRequest( $propertyId ) );
			$httpResponse = $this->getResponseFactory()->create();
			$httpResponse->setHeader( 'Content-Type', 'application/json' );
			$httpResponse->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() ) );
			$this->setEtagFromRevId( $httpResponse, $useCaseResponse->getRevisionId() );
			$httpResponse->setBody(
				new StringStream( json_encode( $this->aliasesSerializer->serialize( $useCaseResponse->getAliases() ) ) )
			);

			return $httpResponse;
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		}
	}

	private function setEtagFromRevId( Response $response, int $revId ): void {
		$response->setHeader( 'ETag', "\"$revId\"" );
	}

	public function needsWriteAccess(): bool {
		return false;
	}

	public function getParamSettings(): array {
		return [
			self::PROPERTY_ID_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}

}

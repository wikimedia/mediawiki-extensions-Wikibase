<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatements;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatementsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatementsResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyStatementsRouteHandler extends SimpleHandler {

	private const PROPERTY_ID_PATH_PARAM = 'property_id';
	private const PROPERTY_ID_QUERY_PARAM = 'property';

	private GetPropertyStatements $useCase;
	private StatementListSerializer $statementListSerializer;
	private ResponseFactory $responseFactory;

	public function __construct(
		GetPropertyStatements $useCase,
		StatementListSerializer $statementListSerializer,
		ResponseFactory $responseFactory
	) {
		$this->useCase = $useCase;
		$this->statementListSerializer = $statementListSerializer;
		$this->responseFactory = $responseFactory;
	}

	public static function factory(): Handler {
		return new self(
			WbRestApi::getGetPropertyStatements(),
			WbRestApi::getSerializerFactory()->newStatementListSerializer(),
			new ResponseFactory()
		);
	}

	public function run( string $subjectPropertyId ): Response {
		$filterPropertyId = $this->getRequest()->getQueryParams()[self::PROPERTY_ID_QUERY_PARAM] ?? null;

		try {
			$useCaseResponse = $this->useCase->execute( new GetPropertyStatementsRequest( $subjectPropertyId, $filterPropertyId ) );
			$httpResponse = $this->newSuccessHttpResponse( $useCaseResponse );
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		}

		return $httpResponse;
	}

	private function newSuccessHttpResponse( GetPropertyStatementsResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() ) );
		$this->setEtagFromRevId( $httpResponse, $useCaseResponse->getRevisionId() );
		$httpResponse->setBody(
			new StringStream(
				json_encode( $this->statementListSerializer->serialize( $useCaseResponse->getStatements() ) )
			)
		);

		return $httpResponse;
	}

	private function setEtagFromRevId( Response $response, int $revId ): void {
		$response->setHeader( 'ETag', "\"$revId\"" );
	}

	public function getParamSettings(): array {
		return [
			self::PROPERTY_ID_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			self::PROPERTY_ID_QUERY_PARAM => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
			],
		];
	}

}

<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatements;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatementsRequest;
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

	public function __construct(
		GetPropertyStatements $useCase,
		StatementListSerializer $statementListSerializer
	) {
		$this->useCase = $useCase;
		$this->statementListSerializer = $statementListSerializer;
	}

	public static function factory(): Handler {
		return new self(
			WbRestApi::getGetPropertyStatements(),
			WbRestApi::getSerializerFactory()->newStatementListSerializer(),
		);
	}

	public function run( string $subjectPropertyId ): Response {
		$filterPropertyId = $this->getRequest()->getQueryParams()[self::PROPERTY_ID_QUERY_PARAM] ?? null;

		$useCaseResponse = $this->useCase->execute( new GetPropertyStatementsRequest( $subjectPropertyId, $filterPropertyId ) );

		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setBody(
			new StringStream(
				json_encode( $this->statementListSerializer->serialize( $useCaseResponse->getStatements() ) )
			)
		);

		return $httpResponse;
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

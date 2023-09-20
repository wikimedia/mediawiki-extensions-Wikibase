<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliases\GetPropertyAliases;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliases\GetPropertyAliasesRequest;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyAliasesRouteHandler extends SimpleHandler {

	private const PROPERTY_ID_PATH_PARAM = 'property_id';

	private GetPropertyAliases $useCase;
	private AliasesSerializer $aliasesSerializer;

	public function __construct(
		GetPropertyAliases $useCase,
		AliasesSerializer $aliasesSerializer
	) {
		$this->useCase = $useCase;
		$this->aliasesSerializer = $aliasesSerializer;
	}

	public static function factory(): self {
		return new self(
			WbRestApi::getGetPropertyAliases(),
			new AliasesSerializer(),
		);
	}

	public function run( string $propertyId ): Response {
		$useCaseResponse = $this->useCase->execute( new GetPropertyAliasesRequest( $propertyId ) );
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setBody(
			new StringStream( json_encode( $this->aliasesSerializer->serialize( $useCaseResponse->getAliases() ) ) )
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
		];
	}

}

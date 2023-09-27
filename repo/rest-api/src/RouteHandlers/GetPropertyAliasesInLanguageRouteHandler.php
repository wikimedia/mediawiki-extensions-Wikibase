<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliasesInLanguage\GetPropertyAliasesInLanguage;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliasesInLanguage\GetPropertyAliasesInLanguageRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliasesInLanguage\GetPropertyAliasesInLanguageResponse;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyAliasesInLanguageRouteHandler extends SimpleHandler {

	private const PROPERTY_ID_PATH_PARAM = 'property_id';
	private const LANGUAGE_CODE_PATH_PARAM = 'language_code';

	private GetPropertyAliasesInLanguage $useCase;

	public function __construct( GetPropertyAliasesInLanguage $useCase ) {
		$this->useCase = $useCase;
	}

	public static function factory(): self {
		return new self( WbRestApi::getGetPropertyAliasesInLanguage() );
	}

	public function run( string $propertyId, string $languageCode ): Response {
		return $this->newSuccessHttpResponse(
			$this->useCase->execute( new GetPropertyAliasesInLanguageRequest( $propertyId, $languageCode ) )
		);
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

	private function newSuccessHttpResponse( GetPropertyAliasesInLanguageResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setBody( new StringStream( json_encode( $useCaseResponse->getAliasesInLanguage()->getAliases() ) ) );

		return $httpResponse;
	}
}

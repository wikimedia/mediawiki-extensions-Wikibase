<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use MediaWiki\Rest\Validator\BodyValidator;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyAliasesInLanguage\AddPropertyAliasesInLanguage;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyAliasesInLanguage\AddPropertyAliasesInLanguageRequest;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyAliasesInLanguage\AddPropertyAliasesInLanguageResponse;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class AddPropertyAliasesInLanguageRouteHandler extends SimpleHandler {

	private const PROPERTY_ID_PATH_PARAM = 'property_id';
	private const LANGUAGE_CODE_PATH_PARAM = 'language_code';
	private const ALIASES_BODY_PARAM = 'aliases';

	private const TAGS_BODY_PARAM = 'tags';
	private const BOT_BODY_PARAM = 'bot';
	private const COMMENT_BODY_PARAM = 'comment';

	private AddPropertyAliasesInLanguage $addPropertyAliases;

	public function __construct( AddPropertyAliasesInLanguage $addPropertyAliases ) {
		$this->addPropertyAliases = $addPropertyAliases;
	}

	public static function factory(): Handler {
		return new self(
			new AddPropertyAliasesInLanguage(
				WbRestApi::getPropertyDataRetriever(),
				WbRestApi::getPropertyUpdater()
			)
		);
	}

	public function run( string $propertyId, string $languageCode ): Response {
		$jsonBody = $this->getValidatedBody();

		return $this->newSuccessHttpResponse(
			$this->addPropertyAliases->execute(
				new AddPropertyAliasesInLanguageRequest(
					$propertyId,
					$languageCode,
					$jsonBody[self::ALIASES_BODY_PARAM],
					$jsonBody[self::TAGS_BODY_PARAM],
					$jsonBody[self::BOT_BODY_PARAM],
					$jsonBody[self::COMMENT_BODY_PARAM],
					$this->getUsername()
				)
			)
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getBodyValidator( $contentType ): BodyValidator {
		return $contentType === 'application/json' ?
			new TypeValidatingJsonBodyValidator( [
				self::ALIASES_BODY_PARAM => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_TYPE => 'array',
					ParamValidator::PARAM_REQUIRED => true,
				],
				self::TAGS_BODY_PARAM => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_TYPE => 'array',
					ParamValidator::PARAM_REQUIRED => false,
					ParamValidator::PARAM_DEFAULT => [],
				],
				self::BOT_BODY_PARAM => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_TYPE => 'boolean',
					ParamValidator::PARAM_REQUIRED => false,
					ParamValidator::PARAM_DEFAULT => false,
				],
				self::COMMENT_BODY_PARAM => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_REQUIRED => false,
				],
			] ) : parent::getBodyValidator( $contentType );
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

	private function newSuccessHttpResponse( AddPropertyAliasesInLanguageResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader(
			'Last-Modified',
			wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() )
		);
		$this->setEtagFromRevId( $httpResponse, $useCaseResponse->getRevisionId() );
		$httpResponse->setBody( new StringStream( json_encode( $useCaseResponse->getAliases()->getAliases() ) ) );

		return $httpResponse;
	}

	private function setEtagFromRevId( Response $httpResponse, int $revId ): void {
		$httpResponse->setHeader( 'ETag', "\"$revId\"" );
	}

	private function getUsername(): ?string {
		$mwUser = $this->getAuthority()->getUser();
		return $mwUser->isRegistered() ? $mwUser->getName() : null;
	}

}

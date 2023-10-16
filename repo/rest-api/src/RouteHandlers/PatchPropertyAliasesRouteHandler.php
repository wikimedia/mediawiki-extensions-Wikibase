<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use MediaWiki\Rest\Validator\BodyValidator;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyAliases\PatchedAliasesValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyAliases\PatchPropertyAliases;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyAliases\PatchPropertyAliasesRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyAliases\PatchPropertyAliasesResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdaterPropertyUpdater;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\PrefetchingTermLookupAliasesRetriever;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\RestApi\Infrastructure\WikibaseRepoAliasesInLanguageValidator;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchPropertyAliasesRouteHandler extends SimpleHandler {

	public const PROPERTY_ID_PATH_PARAM = 'property_id';
	public const PATCH_BODY_PARAM = 'patch';
	public const TAGS_BODY_PARAM = 'tags';
	public const BOT_BODY_PARAM = 'bot';
	public const COMMENT_BODY_PARAM = 'comment';

	private PatchPropertyAliases $useCase;
	private AliasesSerializer $serializer;
	private ResponseFactory $responseFactory;

	public function __construct(
		PatchPropertyAliases $useCase,
		AliasesSerializer $serializer,
		ResponseFactory $responseFactory
	) {
		$this->useCase = $useCase;
		$this->serializer = $serializer;
		$this->responseFactory = $responseFactory;
	}

	public static function factory(): Handler {
		return new self(
			new PatchPropertyAliases(
				WbRestApi::getValidatingRequestDeserializer(),
				WbRestApi::getAssertPropertyExists(),
				WbRestApi::getAssertUserIsAuthorized(),
				new PrefetchingTermLookupAliasesRetriever(
					WikibaseRepo::getPrefetchingTermLookup(),
					WikibaseRepo::getTermsLanguages()
				),
				new AliasesSerializer(),
				new PatchJson( new JsonDiffJsonPatcher() ),
				new PatchedAliasesValidator(
					new AliasesDeserializer(),
					new WikibaseRepoAliasesInLanguageValidator( WikibaseRepo::getTermValidatorFactory() ),
					new LanguageCodeValidator( WikibaseRepo::getTermsLanguages()->getLanguages() )
				),
				WbRestApi::getPropertyDataRetriever(),
				new EntityUpdaterPropertyUpdater(
					WbRestApi::getEntityUpdater(),
					new StatementReadModelConverter(
						WikibaseRepo::getStatementGuidParser(),
						WikibaseRepo::getPropertyDataTypeLookup()
					)
				)
			),
			new AliasesSerializer(),
			new ResponseFactory()
		);
	}

	public function run( string $propertyId ): Response {
		$jsonBody = $this->getValidatedBody();
		try {
			return $this->newSuccessHttpResponse(
				$this->useCase->execute( new PatchPropertyAliasesRequest(
					$propertyId,
					$jsonBody[self::PATCH_BODY_PARAM],
					$jsonBody[self::TAGS_BODY_PARAM],
					$jsonBody[self::BOT_BODY_PARAM],
					$jsonBody[self::COMMENT_BODY_PARAM],
					$this->getUsername()
				) )
			);
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		}
	}

	private function newSuccessHttpResponse( PatchPropertyAliasesResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setStatus( 200 );
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'ETag', "\"{$useCaseResponse->getRevisionId()}\"" );
		$httpResponse->setHeader(
			'Last-Modified',
			wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() )
		);
		$httpResponse->setBody(
			new StringStream(
				json_encode(
					$this->serializer->serialize( $useCaseResponse->getAliases() )
				)
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
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getBodyValidator( $contentType ): BodyValidator {
		return $contentType === 'application/json' || $contentType === 'application/json-patch+json' ?
			new TypeValidatingJsonBodyValidator( [
				self::PATCH_BODY_PARAM => [
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
					ParamValidator::PARAM_DEFAULT => null,
				],
			] ) : parent::getBodyValidator( $contentType );
	}

	private function getUsername(): ?string {
		$mwUser = $this->getAuthority()->getUser();
		return $mwUser->isRegistered() ? $mwUser->getName() : null;
	}

}

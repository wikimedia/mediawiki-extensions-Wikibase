<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\RouteHandlers;

use MediaWiki\HookContainer\HookRunner;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use MediaWiki\Rest\Validator\Validator;
use Wikibase\Repo\Domains\Crud\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\PropertyPartsSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\CreateProperty\CreateProperty;
use Wikibase\Repo\Domains\Crud\Application\UseCases\CreateProperty\CreatePropertyRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\CreateProperty\CreatePropertyResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\PropertyParts;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\BotRightCheckMiddleware;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\TempUserCreationResponseHeaderMiddleware;
use Wikibase\Repo\Domains\Crud\WbCrud;
use Wikibase\Repo\RestApi\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\Middleware\UserAgentCheckMiddleware;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\Timestamp\ConvertibleTimestamp;
use Wikimedia\Timestamp\TimestampFormat as TS;

/**
 * @license GPL-2.0-or-later
 */
class CreatePropertyRouteHandler extends SimpleHandler {

	use AssertValidTopLevelFields;

	private const PROPERTY_BODY_PARAM = 'property';
	private const TAGS_BODY_PARAM = 'tags';
	private const BOT_BODY_PARAM = 'bot';
	private const COMMENT_BODY_PARAM = 'comment';

	private CreateProperty $useCase;
	private PropertyPartsSerializer $propertySerializer;
	private ResponseFactory $responseFactory;
	private MiddlewareHandler $middlewareHandler;

	public function __construct(
		CreateProperty $useCase,
		PropertyPartsSerializer $serializer,
		ResponseFactory $responseFactory,
		MiddlewareHandler $middlewareHandler
	) {
		$this->useCase = $useCase;
		$this->propertySerializer = $serializer;
		$this->responseFactory = $responseFactory;
		$this->middlewareHandler = $middlewareHandler;
	}

	public static function factory(): Handler {
		$responseFactory = new ResponseFactory();

		return new self(
			WbCrud::getCreateProperty(),
			new PropertyPartsSerializer(
				new LabelsSerializer(),
				new DescriptionsSerializer(),
				new AliasesSerializer(),
				new StatementListSerializer( WbCrud::getStatementSerializer() )
			),
			$responseFactory,
			new MiddlewareHandler( [
				WbCrud::getUnexpectedErrorHandlerMiddleware(),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware( MediaWikiServices::getInstance()->getUserIdentityUtils() ),
				new BotRightCheckMiddleware( MediaWikiServices::getInstance()->getPermissionManager(), $responseFactory ),
				new TempUserCreationResponseHeaderMiddleware( new HookRunner( MediaWikiServices::getInstance()->getHookContainer() ) ),
			] )
		);
	}

	public function run(): Response {
		return $this->middlewareHandler->run( $this, $this->runUseCase( ... ) );
	}

	public function runUseCase(): Response {
		$jsonBody = $this->getValidatedBody();
		'@phan-var array $jsonBody'; // guaranteed to be an array per getBodyParamSettings()

		try {
			return $this->newSuccessHttpResponse(
				$this->useCase->execute(
					new CreatePropertyRequest(
						$jsonBody[self::PROPERTY_BODY_PARAM],
						$jsonBody[self::TAGS_BODY_PARAM] ?? [],
						$jsonBody[self::BOT_BODY_PARAM] ?? false,
						$jsonBody[self::COMMENT_BODY_PARAM] ?? null,
						$this->getUsername()
					)
				)
			);
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		}
	}

	public function validate( Validator $restValidator ): void {
		$this->assertValidTopLevelTypes( $this->getRequest()->getParsedBody(), $this->getBodyParamSettings() );
		parent::validate( $restValidator );
	}

	/**
	 * @inheritDoc
	 */
	public function getBodyParamSettings(): array {
		return [
			self::PROPERTY_BODY_PARAM => [
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
		];
	}

	private function newSuccessHttpResponse( CreatePropertyResponse $useCaseResponse ): Response {
		$response = $this->getResponseFactory()->create();
		$response->setStatus( 201 );
		$response->setHeader( 'Content-Type', 'application/json' );
		$response->setHeader(
			'Last-Modified',
			ConvertibleTimestamp::convert( TS::RFC2822, $useCaseResponse->getLastModified() )
		);
		$response->setHeader( 'ETag', "\"{$useCaseResponse->getRevisionId()}\"" );

		$property = $useCaseResponse->getProperty();
		$response->setHeader(
			'Location',
			$this->getRouter()->getRouteUrl(
				GetPropertyRouteHandler::ROUTE,
				[ GetPropertyRouteHandler::PROPERTY_ID_PATH_PARAM => $property->getId() ]
			)
		);

		$response->setBody(
			new StringStream(
				json_encode(
					$this->propertySerializer->serialize(
						new PropertyParts(
							$property->getId(),
							PropertyParts::VALID_FIELDS,
							$property->getDataType(),
							$property->getLabels(),
							$property->getDescriptions(),
							$property->getAliases(),
							$property->getStatements(),
						)
					)
				)
			)
		);

		return $response;
	}

	private function getUsername(): ?string {
		$mwUser = $this->getAuthority()->getUser();
		return $mwUser->isRegistered() ? $mwUser->getName() : null;
	}

}

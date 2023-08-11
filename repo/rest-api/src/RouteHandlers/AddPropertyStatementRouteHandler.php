<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use MediaWiki\Rest\Validator\BodyValidator;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Repo\RestApi\Application\Serialization\StatementSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityRevisionLookupPropertyDataRetriever;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdaterPropertyUpdater;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class AddPropertyStatementRouteHandler extends SimpleHandler {

	public const PROPERTY_ID_PATH_PARAM = 'property_id';
	public const STATEMENT_BODY_PARAM = 'statement';
	public const TAGS_BODY_PARAM = 'tags';
	public const BOT_BODY_PARAM = 'bot';
	public const COMMENT_BODY_PARAM = 'comment';

	private AddPropertyStatement $useCase;
	private StatementSerializer $statementSerializer;
	private ResponseFactory $responseFactory;

	public function __construct(
		AddPropertyStatement $useCase,
		StatementSerializer $statementSerializer,
		ResponseFactory $responseFactory
	) {
		$this->useCase = $useCase;
		$this->statementSerializer = $statementSerializer;
		$this->responseFactory = $responseFactory;
	}

	public static function factory(): self {
		$statementReadModelConverter = new StatementReadModelConverter(
			WikibaseRepo::getStatementGuidParser(),
			WikibaseRepo::getPropertyDataTypeLookup()
		);
		return new self(
			new AddPropertyStatement(
				new AddPropertyStatementValidator( new StatementValidator( WbRestApi::getStatementDeserializer() ) ),
				WbRestApi::getAssertPropertyExists(),
				new EntityRevisionLookupPropertyDataRetriever(
					WikibaseRepo::getEntityRevisionLookup(),
					$statementReadModelConverter
				),
				new GuidGenerator(),
				new EntityUpdaterPropertyUpdater(
					WbRestApi::getEntityUpdater(),
					$statementReadModelConverter
				)
			),
			WbRestApi::getSerializerFactory()->newStatementSerializer(),
			new ResponseFactory()
		);
	}

	public function run( string $propertyId ): Response {
		$body = $this->getValidatedBody();
		try {
			$useCaseResponse = $this->useCase->execute(
				new AddPropertyStatementRequest(
					$propertyId,
					$body[self::STATEMENT_BODY_PARAM],
					$body[self::TAGS_BODY_PARAM],
					$body[self::BOT_BODY_PARAM],
					$body[self::COMMENT_BODY_PARAM],
					$this->getUsername()
				)
			);
			$httpResponse = $this->getResponseFactory()->create();
			$httpResponse->setStatus( 201 );
			$httpResponse->setHeader( 'Content-Type', 'application/json' );
			$httpResponse->setBody( new StringStream( json_encode(
				$this->statementSerializer->serialize( $useCaseResponse->getStatement() )
			) ) );
			return $httpResponse;
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
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getBodyValidator( $contentType ): BodyValidator {
		return $contentType === 'application/json' ?
			new TypeValidatingJsonBodyValidator( [
				self::STATEMENT_BODY_PARAM => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_TYPE => 'object',
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

	private function getUsername(): ?string {
		$mwUser = $this->getAuthority()->getUser();
		return $mwUser->isRegistered() ? $mwUser->getName() : null;
	}

}

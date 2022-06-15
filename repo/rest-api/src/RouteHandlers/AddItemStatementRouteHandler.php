<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use MediaWiki\Rest\Validator\BodyValidator;
use MediaWiki\Rest\Validator\JsonBodyValidator;
use Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter;
use Wikibase\Repo\RestApi\Presentation\Presenters\StatementJsonPresenter;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatement;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatementErrorResponse;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatementRequest;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatementSuccessResponse;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class AddItemStatementRouteHandler extends SimpleHandler {

	public const ITEM_ID_PATH_PARAM = 'item_id';
	public const STATEMENT_BODY_PARAM = 'statement';
	public const TAGS_BODY_PARAM = 'tags';
	public const BOT_BODY_PARAM = 'bot';

	private $addItemStatement;
	private $successPresenter;
	private $responseFactory;
	private $errorHandler;

	public function __construct(
		AddItemStatement $addItemStatement,
		StatementJsonPresenter $successPresenter,
		ResponseFactory $responseFactory,
		UnexpectedErrorHandler $errorHandler
	) {

		$this->addItemStatement = $addItemStatement;
		$this->successPresenter = $successPresenter;
		$this->responseFactory = $responseFactory;
		$this->errorHandler = $errorHandler;
	}

	public static function factory(): Handler {
		$responseFactory = new ResponseFactory( new ErrorJsonPresenter() );
		return new self(
			WbRestApi::getAddItemStatement(),
			new StatementJsonPresenter( WbRestApi::getStatementSerializer() ),
			$responseFactory,
			new UnexpectedErrorHandler( $responseFactory, WikibaseRepo::getLogger() )
		);
	}

	/**
	 * @param mixed ...$args
	 */
	public function run( ...$args ): Response {
		return $this->errorHandler->runWithErrorHandling( [ $this, 'runUseCase' ], $args );
	}

	/**
	 * @throws \Exception
	 */
	public function runUseCase( string $itemId ): Response {
		$jsonBody = $this->getValidatedBody();
		$useCaseResponse = $this->addItemStatement->execute(
			new AddItemStatementRequest(
				$itemId,
				$jsonBody[self::STATEMENT_BODY_PARAM],
				$jsonBody[self::TAGS_BODY_PARAM],
				$jsonBody[self::BOT_BODY_PARAM]
			)
		);

		if ( $useCaseResponse instanceof AddItemStatementSuccessResponse ) {
			$httpResponse = $this->newSuccessHttpResponse( $useCaseResponse );
		} elseif ( $useCaseResponse instanceof AddItemStatementErrorResponse ) {
			$httpResponse = $this->responseFactory->newErrorResponse( $useCaseResponse );
		} else {
			throw new \LogicException( 'Received an unexpected use case result in ' . __CLASS__ );
		}

		$this->addAuthHeaderIfAuthenticated( $httpResponse );

		return $httpResponse;
	}

	public function getParamSettings(): array {
		return [
			self::ITEM_ID_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			]
		];
	}

	/**
	 * @param string $contentType Content type of the request.
	 *
	 * @throws HttpException
	 */
	// phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	public function getBodyValidator( $contentType ): BodyValidator {
		if ( $contentType !== 'application/json' ) {
			throw new HttpException(
				"Unsupported Content-Type", 415, [ 'content_type' => $contentType ]
			);
		}

		return new JsonBodyValidator( [
			self::STATEMENT_BODY_PARAM => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'array',
				ParamValidator::PARAM_REQUIRED => true,
			],
			self::TAGS_BODY_PARAM => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'array',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => []
			],
			self::BOT_BODY_PARAM => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => false
			]
		] );
	}

	private function newSuccessHttpResponse( AddItemStatementSuccessResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setStatus( 201 );
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader(
			'Last-Modified',
			wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() )
		);
		$this->setEtagFromRevId( $httpResponse, $useCaseResponse->getRevisionId() );
		$httpResponse->setBody(
			new StringStream(
				$this->successPresenter->getJson( $useCaseResponse->getStatement() )
			)
		);

		return $httpResponse;
	}

	private function addAuthHeaderIfAuthenticated( Response $httpResponse ): void {
		$user = $this->getAuthority()->getUser();
		if ( $user->isRegistered() ) {
			$httpResponse->setHeader( 'X-Authenticated-User', $user->getName() );
		}
	}

	private function setEtagFromRevId( Response $httpResponse, int $revId ): void {
		$httpResponse->setHeader( 'ETag', "\"$revId\"" );
	}

}

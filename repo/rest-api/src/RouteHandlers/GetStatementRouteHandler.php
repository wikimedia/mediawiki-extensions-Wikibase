<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Presentation\ErrorResponseToHttpStatus;
use Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter;
use Wikibase\Repo\RestApi\Presentation\Presenters\GetItemStatementJsonPresenter;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatement;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatementErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatementRequest;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatementSuccessResponse;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetStatementRouteHandler extends SimpleHandler {

	public const ID_PATH_PARAM = 'statement_id';

	private $getItemStatement;
	private $successPresenter;
	private $errorPresenter;
	private $errorHandler;

	public function __construct(
		GetItemStatement $getItemStatement,
		GetItemStatementJsonPresenter $successPresenter,
		ErrorJsonPresenter $errorPresenter,
		UnexpectedErrorHandler $errorHandler
	) {
		$this->getItemStatement = $getItemStatement;
		$this->successPresenter = $successPresenter;
		$this->errorPresenter = $errorPresenter;
		$this->errorHandler = $errorHandler;
	}

	public static function factory(): Handler {
		$errorPresenter = new ErrorJsonPresenter();
		return new self(
			WbRestApi::getGetItemStatement(),
			new GetItemStatementJsonPresenter(),
			$errorPresenter,
			new UnexpectedErrorHandler( $errorPresenter, WikibaseRepo::getLogger() )
		);
	}

	/**
	 * @param mixed ...$args
	 */
	public function run( ...$args ): Response {
		return $this->errorHandler->runWithErrorHandling( [ $this, 'runUseCase' ], $args );
	}

	public function runUseCase( string $statementId ): Response {
		$useCaseResponse = $this->getItemStatement->execute(
			new GetItemStatementRequest( $statementId )
		);

		if ( $useCaseResponse instanceof GetItemStatementSuccessResponse ) {
			$httpResponse = $this->newSuccessHttpResponse( $useCaseResponse );
		} elseif ( $useCaseResponse instanceof GetItemStatementErrorResponse ) {
			$httpResponse = $this->newErrorHttpResponse( $useCaseResponse );
		} else {
			throw new \LogicException( 'Received an unexpected use case result in ' . __CLASS__ );
		}

		$this->addAuthHeaderIfAuthenticated( $httpResponse );

		return $httpResponse;
	}

	public function getParamSettings(): array {
		return [
			self::ID_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			]
		];
	}

	public function needsWriteAccess(): bool {
		return false;
	}

	private function newSuccessHttpResponse( GetItemStatementSuccessResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader(
			'Last-Modified',
			wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() )
		);
		$this->setEtagFromRevId( $httpResponse, $useCaseResponse->getRevisionId() );
		$httpResponse->setBody(
			new StringStream( $this->successPresenter->getJson( $useCaseResponse ) )
		);

		return $httpResponse;
	}

	private function newErrorHttpResponse( GetItemStatementErrorResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Content-Language', 'en' );
		$httpResponse->setStatus( ErrorResponseToHttpStatus::lookup( $useCaseResponse ) );
		$httpResponse->setBody(
			new StringStream( $this->errorPresenter->getJson( $useCaseResponse ) )
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

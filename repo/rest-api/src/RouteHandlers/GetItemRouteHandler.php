<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\ConditionalHeaderUtil;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Presentation\ErrorResponseToHttpStatus;
use Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter;
use Wikibase\Repo\RestApi\Presentation\Presenters\GetItemJsonPresenter;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemRequest;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemSuccessResponse;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetItemRouteHandler extends SimpleHandler {

	/**
	 * @var GetItem
	 */
	private $getItem;

	/**
	 * @var GetItemJsonPresenter
	 */
	private $successPresenter;

	/**
	 * @var ErrorJsonPresenter
	 */
	private $errorPresenter;

	/**
	 * @var UnexpectedErrorHandler
	 */
	private $errorHandler;

	public function __construct(
		GetItem $getItem,
		GetItemJsonPresenter $presenter,
		ErrorJsonPresenter $errorPresenter,
		UnexpectedErrorHandler $errorHandler
	) {
		$this->getItem = $getItem;
		$this->successPresenter = $presenter;
		$this->errorPresenter = $errorPresenter;
		$this->errorHandler = $errorHandler;
	}

	public static function factory(): Handler {
		$errorPresenter = new ErrorJsonPresenter();
		return new self(
			WbRestApi::getGetItem(),
			new GetItemJsonPresenter(),
			$errorPresenter,
			new UnexpectedErrorHandler( $errorPresenter )
		);
	}

	/**
	 * @param mixed ...$args
	 */
	public function run( ...$args ): Response {
		return $this->errorHandler->runWithErrorHandling( [ $this, 'runUseCase' ], $args );
	}

	public function runUseCase( string $id ): Response {
		$fields = explode( ',', $this->getValidatedParams()['_fields'] );
		$useCaseResponse = $this->getItem->execute( new GetItemRequest( $id, $fields ) );

		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );

		if ( $useCaseResponse instanceof GetItemSuccessResponse ) {

			// This performs a *precondition* check post use case execution. Maybe needs to be moved into the use case in other scenarios.
			// A drawback of doing this check here is that we already fetched and serialized a whole Item object.
			if ( $this->isNotModified( $useCaseResponse->getRevisionId(), $useCaseResponse->getLastModified() ) ) {
				$notModifiedResponse = $this->getResponseFactory()->createNotModified();
				$notModifiedResponse->setHeader( 'ETag', $useCaseResponse->getRevisionId() );
				return $notModifiedResponse;
			}

			$httpResponse->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() ) );
			$httpResponse->setHeader( 'ETag', $useCaseResponse->getRevisionId() );
			$httpResponse->setBody( new StringStream( $this->successPresenter->getJson( $useCaseResponse ) ) );
		} elseif ( $useCaseResponse instanceof GetItemErrorResponse ) {
			$httpResponse->setHeader( 'Content-Language', 'en' );
			$httpResponse->setStatus( ErrorResponseToHttpStatus::lookup( $useCaseResponse ) );
			$httpResponse->setBody( new StringStream( $this->errorPresenter->getJson( $useCaseResponse ) ) );
		} else {
			throw new \LogicException( 'Received an unexpected use case result in ' . __CLASS__ );
		}

		$this->addAuthHeaderIfAuthenticated( $httpResponse );

		return $httpResponse;
	}

	public function getParamSettings(): array {
		return [
			'id' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'_fields' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_ISMULTI => false,
				ParamValidator::PARAM_DEFAULT => implode( ',', GetItemRequest::VALID_FIELDS )
			],
		];
	}

	private function addAuthHeaderIfAuthenticated( Response $response ): void {
		$user = $this->getAuthority()->getUser();
		if ( $user->isRegistered() ) {
			$response->setHeader( 'X-Authenticated-User', $user->getName() );
		}
	}

	private function isNotModified( int $revId, string $lastModifiedDate ): bool {
		$headerUtil = new ConditionalHeaderUtil();
		$headerUtil->setValidators( "\"$revId\"", $lastModifiedDate, true );

		return $headerUtil->checkPreconditions( $this->getRequest() ) === 304;
	}
}

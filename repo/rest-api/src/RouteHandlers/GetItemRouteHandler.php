<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Presentation\ErrorResultToHttpStatus;
use Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter;
use Wikibase\Repo\RestApi\Presentation\Presenters\GetItemJsonPresenter;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemErrorResult;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemRequest;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemSuccessResult;
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
		return WbRestApi::getRouteHandlerFeatureToggle()->useHandlerIfEnabled(
			new self(
				WbRestApi::getGetItem(),
				new GetItemJsonPresenter(),
				$errorPresenter,
				new UnexpectedErrorHandler( $errorPresenter )
			)
		);
	}

	/**
	 * @param mixed ...$args
	 */
	public function run( ...$args ): Response {
		return $this->errorHandler->runWithErrorHandling( [ $this, 'runUseCase' ], $args );
	}

	public function runUseCase( string $id ): Response {
		$fields = $this->getValidatedParams()['_fields'];
		$result = $this->getItem->execute( new GetItemRequest( $id, $fields ) );

		$response = $this->getResponseFactory()->create();
		$response->setHeader( 'Content-Type', 'application/json' );

		if ( $result instanceof GetItemSuccessResult ) {
			$response->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $result->getLastModified() ) );
			$response->setHeader( 'ETag', $result->getRevisionId() );
			$response->setBody( new StringStream( $this->successPresenter->getJson( $result ) ) );
		} elseif ( $result instanceof GetItemErrorResult ) {
			$response->setHeader( 'Content-Language', 'en' );
			$response->setStatus( ErrorResultToHttpStatus::lookup( $result ) );
			$response->setBody( new StringStream( $this->errorPresenter->getJson( $result ) ) );
		} else {
			throw new \LogicException( 'Received an unexpected use case result in ' . __CLASS__ );
		}

		return $response;
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
				// TODO: need to decide on "pipe and array" vs "comma" (vs "pipe, array, and comma"?)
				ParamValidator::PARAM_ISMULTI => true,
			],
		];
	}
}

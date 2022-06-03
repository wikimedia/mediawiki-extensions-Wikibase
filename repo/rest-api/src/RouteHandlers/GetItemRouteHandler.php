<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\DataModel\Serializers\SiteLinkListSerializer;
use Wikibase\Repo\RestApi\Domain\Model\ItemData;
use Wikibase\Repo\RestApi\Domain\Serializers\ItemDataSerializer;
use Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter;
use Wikibase\Repo\RestApi\Presentation\Presenters\GetItemJsonPresenter;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemRequest;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemSuccessResponse;
use Wikibase\Repo\RestApi\UseCases\ItemRedirectResponse;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetItemRouteHandler extends SimpleHandler {
	use ConditionalRequestsHelper;

	private const ID_PATH_PARAM = 'id';
	private const FIELDS_QUERY_PARAM = '_fields';

	/**
	 * @var GetItem
	 */
	private $getItem;

	/**
	 * @var GetItemJsonPresenter
	 */
	private $successPresenter;

	/**
	 * @var ResponseFactory
	 */
	private $responseFactory;

	/**
	 * @var UnexpectedErrorHandler
	 */
	private $errorHandler;

	public function __construct(
		GetItem $getItem,
		GetItemJsonPresenter $presenter,
		ResponseFactory $responseFactory,
		UnexpectedErrorHandler $errorHandler
	) {
		$this->getItem = $getItem;
		$this->successPresenter = $presenter;
		$this->responseFactory = $responseFactory;
		$this->errorHandler = $errorHandler;
	}

	public static function factory(): Handler {
		$serializerFactory = WbRestApi::getBaseDataModelSerializerFactory();
		$responseFactory = new ResponseFactory( new ErrorJsonPresenter() );
		return new self(
			WbRestApi::getGetItem(),
			new GetItemJsonPresenter( new ItemDataSerializer(
				WbRestApi::getStatementListSerializer(),
				new SiteLinkListSerializer( $serializerFactory->newSiteLinkSerializer(), true )
			) ),
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

	public function runUseCase( string $id ): Response {
		$fields = explode( ',', $this->getValidatedParams()[self::FIELDS_QUERY_PARAM] );
		$useCaseResponse = $this->getItem->execute( new GetItemRequest( $id, $fields ) );

		if ( $useCaseResponse instanceof GetItemSuccessResponse ) {
			$httpResponse = $this->newSuccessHttpResponse( $useCaseResponse );
		} elseif ( $useCaseResponse instanceof ItemRedirectResponse ) {
			$httpResponse = $this->newRedirectHttpResponse( $useCaseResponse );
		} elseif ( $useCaseResponse instanceof GetItemErrorResponse ) {
			$httpResponse = $this->responseFactory->newErrorResponse( $useCaseResponse );
		} else {
			throw new \LogicException( 'Received an unexpected use case result in ' . __CLASS__ );
		}

		$this->addAuthHeaderIfAuthenticated( $httpResponse );

		return $httpResponse;
	}

	private function newSuccessHttpResponse( GetItemSuccessResponse $useCaseResponse ): Response {
		$revId = $useCaseResponse->getRevisionId();

		// This performs a *precondition* check post use case execution. Maybe needs to be moved into the use case in other scenarios.
		// A drawback of doing this check here is that we already fetched and serialized a whole Item object.
		if ( $this->isNotModified( $revId, $useCaseResponse->getLastModified() ) ) {
			return $this->newNotModifiedResponse( $revId );
		}

		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() ) );
		$this->setEtagFromRevId( $httpResponse, $revId );
		$httpResponse->setBody( new StringStream( $this->successPresenter->getJson( $useCaseResponse ) ) );

		return $httpResponse;
	}

	private function newRedirectHttpResponse( ItemRedirectResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader(
			'Location',
			$this->getRouteUrl(
				[ self::ID_PATH_PARAM => $useCaseResponse->getRedirectTargetId() ],
				$this->getRequest()->getQueryParams()
			)
		);
		$httpResponse->setStatus( 308 );

		return $httpResponse;
	}

	private function setEtagFromRevId( Response $response, int $revId ): void {
		$response->setHeader( 'ETag', "\"$revId\"" );
	}

	public function getParamSettings(): array {
		return [
			self::ID_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			self::FIELDS_QUERY_PARAM => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_ISMULTI => false,
				ParamValidator::PARAM_DEFAULT => implode( ',', ItemData::VALID_FIELDS )
			],
		];
	}

	public function needsWriteAccess(): bool {
		return false;
	}

	private function addAuthHeaderIfAuthenticated( Response $response ): void {
		$user = $this->getAuthority()->getUser();
		if ( $user->isRegistered() ) {
			$response->setHeader( 'X-Authenticated-User', $user->getName() );
		}
	}

}

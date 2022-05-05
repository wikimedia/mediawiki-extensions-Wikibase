<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Presentation\Presenters\GetItemStatementsJsonPresenter;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatements;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsRequest;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsSuccessResponse;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatementsRouteHandler extends SimpleHandler {
	private const ID_PATH_PARAM = 'id';

	/**
	 * @var GetItemStatements
	 */
	private $getItemStatements;

	/**
	 * @var GetItemStatementsJsonPresenter
	 */
	private $successPresenter;

	public function __construct(
		GetItemStatements $getItemStatements,
		GetItemStatementsJsonPresenter $presenter
	) {
		$this->getItemStatements = $getItemStatements;
		$this->successPresenter = $presenter;
	}

	public static function factory(): Handler {
		return new self(
			WbRestApi::getGetItemStatements(),
			new GetItemStatementsJsonPresenter()
		);
	}

	public function run( string $itemId ): Response {
		$useCaseResponse = $this->getItemStatements->execute( new GetItemStatementsRequest( $itemId ) );

		$httpResponse = $this->newSuccessHttpResponse( $useCaseResponse );

		$this->addAuthHeaderIfAuthenticated( $httpResponse );

		return $httpResponse;
	}

	private function newSuccessHttpResponse( GetItemStatementsSuccessResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );

		$httpResponse->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() ) );
		$this->setEtagFromRevId( $httpResponse, $useCaseResponse->getRevisionId() );
		$httpResponse->setBody( new StringStream( $this->successPresenter->getJson( $useCaseResponse ) ) );

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

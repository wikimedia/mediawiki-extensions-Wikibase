<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Presentation\ErrorReporterToHttpStatus;
use Wikibase\Repo\RestApi\Presentation\Presenters\GetItemJsonPresenter;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemRequest;
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
	private $presenter;

	public function __construct( GetItem $getItem, GetItemJsonPresenter $presenter ) {
		$this->getItem = $getItem;
		$this->presenter = $presenter;
	}

	public static function factory(): Handler {
		return WbRestApi::getRouteHandlerFeatureToggle()->useHandlerIfEnabled(
			new self( WbRestApi::getGetItem(), new GetItemJsonPresenter() )
		);
	}

	public function run( string $id ): Response {
		$result = $this->getItem->execute( new GetItemRequest( $id ) );

		$response = $this->getResponseFactory()->create();
		$response->setHeader( 'Content-Type', 'application/json' );

		if ( $result->isSuccessful() ) {
			$response->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $result->getLastModified() ) );
			$response->setHeader( 'ETag', $result->getRevisionId() );
		} else {
			$response->setHeader( 'Content-Language', 'en' );
			$response->setStatus( ErrorReporterToHttpStatus::lookup( $result->getError() ) );
		}

		$response->setBody( new StringStream( $this->presenter->getJsonItem( $result ) ) );

		return $response;
	}

	public function getParamSettings(): array {
		return [
			'id' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}
}

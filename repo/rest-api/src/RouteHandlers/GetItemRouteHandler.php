<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
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

	public function __construct( GetItem $getItem ) {
		$this->getItem = $getItem;
	}

	public static function factory(): Handler {
		return WbRestApi::getRouteHandlerFeatureToggle()->useHandlerIfEnabled(
			new self( WbRestApi::getGetItem() )
		);
	}

	public function run( string $id ): Response {
		$result = $this->getItem->execute( new GetItemRequest( $id ) );
		$response = $this->getResponseFactory()->create();
		$response->setHeader( 'Content-Type', 'application/json' );
		$response->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $result->getLastModified() ) );
		$response->setHeader( 'ETag', $result->getRevisionId() );
		$response->setBody( new StringStream( json_encode( $result->getItem() ) ) );

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

<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemSiteLink\RemoveItemSiteLink;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemSiteLink\RemoveItemSiteLinkRequest;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class RemoveItemSiteLinkRouteHandler extends SimpleHandler {

	private const ITEM_ID_PATH_PARAM = 'item_id';
	private const SITE_ID_PATH_PARAM = 'site_id';
	private RemoveItemSiteLink $useCase;

	public static function factory(): self {
		return new self(
			new RemoveItemSiteLink( WbRestApi::getItemDataRetriever(), WbRestApi::getItemUpdater() )
		);
	}

	public function __construct( RemoveItemSiteLink $useCase ) {
		$this->useCase = $useCase;
	}

	public function run( string $itemId, string $siteId ): Response {
		$this->useCase->execute( new RemoveItemSiteLinkRequest( $itemId, $siteId ) );
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );

		$httpResponse->setBody(
			new StringStream( '"Sitelink deleted"' )
		);

		return $httpResponse;
	}

	public function getParamSettings(): array {
		return [
			self::ITEM_ID_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			self::SITE_ID_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}
}

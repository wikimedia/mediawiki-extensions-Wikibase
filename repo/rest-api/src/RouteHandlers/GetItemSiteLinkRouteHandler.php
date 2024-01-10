<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\RestApi\Application\Serialization\SiteLinksSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemSiteLink\GetItemSiteLink;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemSiteLink\GetItemSiteLinkRequest;
use Wikibase\Repo\RestApi\Domain\ReadModel\SiteLinks;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetItemSiteLinkRouteHandler extends SimpleHandler {

	private const ITEM_ID_PATH_PARAM = 'item_id';
	private const SITE_ID_PATH_PARAM = 'site_id';
	private GetItemSiteLink $useCase;
	private SiteLinksSerializer $siteLinksSerializer;

	public static function factory(): self {
		return new GetItemSiteLinkRouteHandler(
			new GetItemSiteLink( WbRestApi::getGetLatestItemRevisionMetadata(), WbRestApi::getSiteLinksRetriever() ),
			new SiteLinksSerializer()
		);
	}

	public function __construct( GetItemSiteLink $useCase, SiteLinksSerializer $siteLinksSerializer ) {
		$this->useCase = $useCase;

		$this->siteLinksSerializer = $siteLinksSerializer;
	}

	public function run( string $itemId, string $siteId ): Response {
		$useCaseResponse = $this->useCase->execute( new GetItemSiteLinkRequest( $itemId, $siteId ) );
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Last-Modified', wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() ) );
		$this->setEtagFromRevId( $httpResponse, $useCaseResponse->getRevisionId() );
		$siteLinks = $useCaseResponse->getSiteLink();

		$siteLinkSerialization = $this->siteLinksSerializer->serialize(
			new SiteLinks( $siteLinks )
		)[$siteLinks->getSite()];
		$httpResponse->setBody(
			new StringStream(
				json_encode( $siteLinkSerialization, JSON_UNESCAPED_SLASHES )
			)
		);

		return $httpResponse;
	}

	private function setEtagFromRevId( Response $response, int $revId ): void {
		$response->setHeader( 'ETag', "\"$revId\"" );
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

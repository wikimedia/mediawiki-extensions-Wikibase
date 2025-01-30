<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;

/**
 * @license GPL-2.0-or-later
 */
class GetOpenApiDocRouteHandler extends SimpleHandler {

	private const OPENAPI_FILE = __DIR__ . '/openapi.json';

	public static function factory(): Handler {
		return new self();
	}

	public function run(): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );

		$httpResponse->setBody( new StringStream( file_get_contents( self::OPENAPI_FILE ) ) );

		return $httpResponse;
	}

	public function needsWriteAccess(): bool {
		return false;
	}

}

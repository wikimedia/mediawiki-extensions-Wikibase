<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;

/**
 * @license GPL-2.0-or-later
 */
class ContentTypeCheckMiddleware implements Middleware {

	public const TYPE_APPLICATION_JSON = 'application/json';
	public const TYPE_JSON_PATCH = 'application/json-patch+json';
	public const TYPE_NONE = '';

	private array $allowedContentTypes;

	public function __construct( array $allowedContentTypes ) {
		$this->allowedContentTypes = $allowedContentTypes;
	}

	public function run( Handler $handler, callable $runNext ): Response {
		$contentType = $this->getContentType( $handler->getRequest() );

		if ( !in_array( $contentType, $this->allowedContentTypes ) ) {
			return $handler->getResponseFactory()->createHttpError(
				415,
				[
					'code' => 'unsupported-content-type',
					'message' => "Unsupported Content-Type: '$contentType'",
				]
			);
		}

		return $runNext();
	}

	// use the helper trait if Ie8650198c4afde4721da78ca506548f32732765d gets merged
	private function getContentType( RequestInterface $request ): string {
		list( $ct ) = explode( ';', $request->getHeaderLine( 'Content-Type' ), 2 );

		return strtolower( trim( $ct ) );
	}

}

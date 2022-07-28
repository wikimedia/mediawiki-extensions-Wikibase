<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;

/**
 * Middleware handling conditional requests with If-None-Match and If-Modified-Since headers.
 *
 * @license GPL-2.0-or-later
 */
class NotModifiedPreconditionMiddleware implements Middleware {

	private $preconditionCheck;

	public function __construct( RequestPreconditionCheck $preconditionCheck ) {
		$this->preconditionCheck = $preconditionCheck;
	}

	public function run( Handler $handler, callable $runNext ): Response {
		$preconditionCheckResult = $this->preconditionCheck->checkPreconditions( $handler->getRequest() );
		if ( $preconditionCheckResult->getStatusCode() === 304 ) {
			return $this->newNotModifiedResponse( $handler,  $preconditionCheckResult->getRevisionMetadata()->getRevisionId() );
		}

		return $runNext();
	}

	private function newNotModifiedResponse( Handler $handler, int $revId ): Response {
		$notModifiedResponse = $handler->getResponseFactory()->createNotModified();
		$notModifiedResponse->setHeader( 'ETag', "\"$revId\"" );

		return $notModifiedResponse;
	}

}

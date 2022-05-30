<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\ConditionalHeaderUtil;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;

/**
 * @license GPL-2.0-or-later
 */
trait ConditionalRequestsHelper {

	private function isNotModified( int $revId, string $lastModifiedDate ): bool {
		/** @var Handler $this */
		'@phan-var Handler $this';

		$headerUtil = new ConditionalHeaderUtil();
		$headerUtil->setValidators( "\"$revId\"", $lastModifiedDate, true );

		return $headerUtil->checkPreconditions( $this->getRequest() ) === 304;
	}

	private function newNotModifiedResponse( int $revId ): Response {
		/** @var Handler $this */
		'@phan-var Handler $this';

		$notModifiedResponse = $this->getResponseFactory()->createNotModified();
		$notModifiedResponse->setHeader( 'ETag', "\"$revId\"" );

		return $notModifiedResponse;
	}

}

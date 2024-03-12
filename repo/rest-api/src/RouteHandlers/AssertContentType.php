<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\RequestData;
use MediaWiki\Rest\Validator\UnsupportedContentTypeBodyValidator;

/**
 * @license GPL-2.0-or-later
 */
trait AssertContentType {

	/**
	 * @throws \MediaWiki\Rest\HttpException
	 */
	private function assertContentType( array $allowedTypes, string $contentType ): void {
		if ( !in_array( $contentType, $allowedTypes ) ) {
			// Using UnsupportedContentTypeBodyValidator to throw the 415 status exception to ensure we use the same error code and message.
			( new UnsupportedContentTypeBodyValidator( $contentType ) )->validateBody( new RequestData() );
		}
	}

}

<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers\Middleware;

use MediaWiki\Rest\ConditionalHeaderUtil;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;

/**
 * @license GPL-2.0-or-later
 */
class PreconditionMiddlewareFactory {

	private ItemRevisionMetadataRetriever $metadataRetriever;
	private ConditionalHeaderUtil $conditionalHeaderUtil;

	public function __construct( ItemRevisionMetadataRetriever $metadataRetriever, ConditionalHeaderUtil $conditionalHeaderUtil ) {
		$this->metadataRetriever = $metadataRetriever;
		$this->conditionalHeaderUtil = $conditionalHeaderUtil;
	}

	public function newPreconditionMiddleware( callable $getItemFromRequest ): PreconditionMiddleware {
		return new PreconditionMiddleware( new RequestPreconditionCheck(
			$this->metadataRetriever,
			$getItemFromRequest,
			$this->conditionalHeaderUtil
		) );
	}

}

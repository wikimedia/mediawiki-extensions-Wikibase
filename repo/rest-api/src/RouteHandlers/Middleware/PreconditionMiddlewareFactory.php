<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers\Middleware;

use MediaWiki\Rest\ConditionalHeaderUtil;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;

/**
 * @license GPL-2.0-or-later
 */
class PreconditionMiddlewareFactory {

	private $metadataRetriever;
	private $conditionalHeaderUtil;

	public function __construct( ItemRevisionMetadataRetriever $metadataRetriever, ConditionalHeaderUtil $conditionalHeaderUtil ) {
		$this->metadataRetriever = $metadataRetriever;
		$this->conditionalHeaderUtil = $conditionalHeaderUtil;
	}

	public function newNotModifiedPreconditionMiddleware( callable $getItemFromRequest ): NotModifiedPreconditionMiddleware {
		return new NotModifiedPreconditionMiddleware( new RequestPreconditionCheck(
			$this->metadataRetriever,
			$getItemFromRequest,
			$this->conditionalHeaderUtil
		) );
	}

	public function newModifiedPreconditionMiddleware( callable $getItemFromRequest ): ModifiedPreconditionMiddleware {
		return new ModifiedPreconditionMiddleware( new RequestPreconditionCheck(
			$this->metadataRetriever,
			$getItemFromRequest,
			$this->conditionalHeaderUtil
		) );
	}

}

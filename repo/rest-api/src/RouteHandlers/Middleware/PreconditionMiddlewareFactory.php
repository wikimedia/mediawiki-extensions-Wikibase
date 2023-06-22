<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers\Middleware;

use MediaWiki\Rest\ConditionalHeaderUtil;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Store\EntityRevisionLookup;

/**
 * @license GPL-2.0-or-later
 */
class PreconditionMiddlewareFactory {

	private EntityRevisionLookup $revisionLookup;
	private EntityIdParser $entityIdParser;
	private ConditionalHeaderUtil $conditionalHeaderUtil;

	public function __construct(
		EntityRevisionLookup $revisionLookup,
		EntityIdParser $entityIdParser,
		ConditionalHeaderUtil $conditionalHeaderUtil
	) {
		$this->conditionalHeaderUtil = $conditionalHeaderUtil;
		$this->revisionLookup = $revisionLookup;
		$this->entityIdParser = $entityIdParser;
	}

	public function newPreconditionMiddleware( callable $getEntityIdFromRequest ): PreconditionMiddleware {
		return new PreconditionMiddleware( new RequestPreconditionCheck(
			$this->revisionLookup,
			$this->entityIdParser,
			$getEntityIdFromRequest,
			$this->conditionalHeaderUtil
		) );
	}

}

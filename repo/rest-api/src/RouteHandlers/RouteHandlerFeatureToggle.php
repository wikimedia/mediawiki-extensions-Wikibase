<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;

/**
 * @license GPL-2.0-or-later
 */
class RouteHandlerFeatureToggle {

	private $restApiEnabled;
	private $apiNotEnabledRouteHandler;

	public function __construct( bool $restApiEnabled, ApiNotEnabledRouteHandler $apiNotEnabledRouteHandler ) {
		$this->restApiEnabled = $restApiEnabled;
		$this->apiNotEnabledRouteHandler = $apiNotEnabledRouteHandler;
	}

	public function useHandlerIfEnabled( Handler $handler ): Handler {
		return $this->restApiEnabled ? $handler : $this->apiNotEnabledRouteHandler;
	}

}

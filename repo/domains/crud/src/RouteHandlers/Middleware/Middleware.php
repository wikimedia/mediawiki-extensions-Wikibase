<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;

/**
 * @license GPL-2.0-or-later
 */
interface Middleware {

	public function run( Handler $routeHandler, callable $runNext ): Response;

}

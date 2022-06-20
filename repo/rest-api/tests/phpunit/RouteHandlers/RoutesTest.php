<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RoutesTest extends TestCase {

	public function testRoutesMatch(): void {
		$objectFactory = MediaWikiServices::getInstance()->getObjectFactory();
		$routes = json_decode( file_get_contents( __DIR__ . '/../../../routes.json' ), true );

		foreach ( $routes as $routeData ) {
			$route = $objectFactory->createObject( $routeData );
			if ( defined( get_class( $route ) . '::ROUTE' ) ) {
				$this->assertSame( $routeData['path'], $route::ROUTE );
			}
		}
	}

}

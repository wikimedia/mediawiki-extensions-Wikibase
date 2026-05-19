<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\RouteHandlers;

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
		$getFlattenedRoutes = static function( array $moduleInfo ) {
			$routes = [];
			foreach ( $moduleInfo['paths'] as $path => $infoByMethod ) {
				foreach ( $infoByMethod as $method => $routeInfo ) {
					$routes[] = array_merge(
						[
							'path' => '/' . $moduleInfo['moduleId'] . $path,
							'method' => strtoupper( $method ),
						],
						$routeInfo['handler']
					);
				}
			}

			return $routes;
		};
		$routes = array_merge(
			json_decode( file_get_contents( __DIR__ . '/../../../../../../extension-repo.json' ), true )[ 'RestRoutes' ],
			$getFlattenedRoutes( json_decode( file_get_contents( __DIR__ . '/../../../../../rest-api/wikibase.v1.json' ), true ) ),
			json_decode( file_get_contents( __DIR__ . '/../../../../../rest-api/routes.dev.json' ), true ),
		);

		foreach ( $routes as $routeData ) {
			$route = $objectFactory->createObject( $routeData );
			if ( defined( get_class( $route ) . '::ROUTE' ) ) {
				$this->assertSame( $routeData['path'], $route::ROUTE );
			}
		}
	}

}

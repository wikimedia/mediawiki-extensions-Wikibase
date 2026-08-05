<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\Hooks\WikibaseRepoHookRunner;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 */
class GetOpenApiDocRouteHandler extends SimpleHandler {

	private const OPENAPI_FILE = __DIR__ . '/openapi.json';

	// the document's paths are relative to the ".../rest.php/wikibase" server
	private const SERVER_PREFIX = '/wikibase';

	private WikibaseRepoHookRunner $hookRunner;

	public function __construct( WikibaseRepoHookRunner $hookRunner ) {
		$this->hookRunner = $hookRunner;
	}

	public static function factory(): Handler {
		return new self( WikibaseRepo::getHookRunner() );
	}

	public function run(): Response {
		$joiner = new OpenApiDocFragmentJoiner(
			file_get_contents( self::OPENAPI_FILE ),
			$this->getRoutablePaths()
		);
		$this->hookRunner->onWikibaseRepoOpenApiDocFragments( $joiner );

		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );

		$httpResponse->setBody( new StringStream( $joiner->getDocumentJson() ) );

		return $httpResponse;
	}

	public function needsWriteAccess(): bool {
		return false;
	}

	/**
	 * The paths the wiki's REST router can actually serve, relative to the
	 * document's server. Restricting the join to these is what lets
	 * extensions register their fragments unconditionally: the served
	 * document never describes a route the wiki cannot serve, however the
	 * extensions' routes are registered.
	 *
	 * @return string[]
	 */
	private function getRoutablePaths(): array {
		$router = $this->getRouter();

		$paths = [];
		foreach ( $router->getModuleIds() as $moduleId ) {
			$module = $router->getModule( $moduleId );
			if ( !$module ) {
				continue;
			}
			$modulePrefix = $moduleId === '' ? '' : '/' . $moduleId;
			foreach ( $module->getDefinedPaths() as $path => $_methods ) {
				$fullPath = $modulePrefix . $path;
				if ( str_starts_with( $fullPath, self::SERVER_PREFIX . '/' ) ) {
					$paths[] = substr( $fullPath, strlen( self::SERVER_PREFIX ) );
				}
			}
		}

		return $paths;
	}

}

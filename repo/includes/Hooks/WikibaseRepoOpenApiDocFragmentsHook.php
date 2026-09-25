<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use Wikibase\Repo\RestApi\OpenApiDocFragmentJoiner;

/**
 * Hook for joining self-contained, dereferenced OpenAPI doc fragments into
 * the document served at /wikibase/v1/openapi.json.
 *
 * Joining may be unconditional: of each fragment, only the paths the wiki's
 * REST router can actually serve end up in the served document. Each joined
 * fragment must be self-contained: nothing resolves `$ref`s at runtime.
 * Paths are relative to the document's ".../rest.php" server, so they
 * include the route's module prefix (e.g. "/wikibase/v1/entities/items").
 *
 * @license GPL-2.0-or-later
 */
interface WikibaseRepoOpenApiDocFragmentsHook {

	public function onWikibaseRepoOpenApiDocFragments( OpenApiDocFragmentJoiner $joiner ): void;

}

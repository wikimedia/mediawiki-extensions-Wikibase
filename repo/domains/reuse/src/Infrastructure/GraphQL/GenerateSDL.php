<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL;

use GraphQL\Utils\SchemaPrinter;
use MediaWiki\Maintenance\Maintenance;
use Wikibase\Repo\Domains\Reuse\WbReuse;

/**
 * Maintenance script to generate the Wikibase GraphQL schema SDL
 *
 * @license GPL-2.0-or-later
 */
class GenerateSDL extends Maintenance {
	public function execute(): void {
		file_put_contents(
			__DIR__ . '/schema.graphql',
			SchemaPrinter::doPrint( WbReuse::getGraphQLSchema() )
		);
	}
}

$maintClass = GenerateSDL::class;

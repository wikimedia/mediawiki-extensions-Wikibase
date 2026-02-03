<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL;

use GraphQL\Utils\SchemaPrinter;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\Domains\Reuse\WbReuse;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SchemaTest extends MediaWikiIntegrationTestCase {

	public function testSchemaIsUpToDate(): void {
		// This ensures that there is at least one data type using the generic EntityValue type to avoid generating different schemas
		// based on the available extensions on the test system.
		$this->setTemporaryHook( 'WikibaseRepoDataTypes', function ( array &$dataTypes ): void {
			$dataTypes[ 'PT:some-other-entity-type' ] = [
				'value-type' => 'wikibase-entityid',
			];
		} );

		$this->assertSame(
			SchemaPrinter::doPrint( WbReuse::getGraphQLSchema() ),
			file_get_contents( __DIR__ . '/../../../../src/Infrastructure/GraphQL/schema.graphql' ),
			'The GraphQL schema is different from the definition in schema.graphql'
		);
	}

}

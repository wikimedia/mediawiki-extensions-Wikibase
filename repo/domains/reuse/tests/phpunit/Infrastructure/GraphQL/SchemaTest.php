<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL;

use GraphQL\GraphQL;
use GraphQL\Utils\SchemaPrinter;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Reuse\WbReuse;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SchemaTest extends TestCase {

	public static function setUpBeforeClass(): void {
		if ( !class_exists( GraphQL::class ) ) {
			self::markTestSkipped( 'Needs webonyx/graphql-php to run' );
		}
	}

	public function testSchemaIsUpToDate(): void {
		$this->assertSame(
			SchemaPrinter::doPrint( WbReuse::getGraphQLSchema() ),
			file_get_contents( __DIR__ . '/../../../../src/Infrastructure/GraphQL/schema.graphql' ),
			'The GraphQL schema is different from the definition in schema.graphql'
		);
	}

}

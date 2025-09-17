<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLQueryService;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLQueryService
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GraphQLQueryServiceTest extends TestCase {
	public function test(): void {
		$this->assertInstanceOf(
			GraphQLQueryService::class,
			new GraphQLQueryService()
		);
	}
}

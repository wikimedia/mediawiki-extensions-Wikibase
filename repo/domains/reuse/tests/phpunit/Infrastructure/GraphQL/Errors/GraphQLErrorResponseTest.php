<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL\Errors;

use GraphQL\Error\SyntaxError;
use GraphQL\Language\Source;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\GraphQLErrorResponse;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\GraphQLErrorResponse
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GraphQLErrorResponseTest extends TestCase {

	public function testFromArray(): void {
		$error = [ 'message' => 'Something went wrong' ];

		$this->assertSame( [ 'errors' => [ $error ] ], GraphQLErrorResponse::fromArray( $error ) );
	}

	public function testFromSyntaxError(): void {
		$src = new Source( 'query { foo }' );
		$e = new SyntaxError( $src, 0, 'Expected Name, found <EOF>' );

		$result = GraphQLErrorResponse::fromSyntaxError( $e );

		$this->assertSame( 'Invalid query - ' . $e->getMessage(), $result['errors'][0]['message'] );
	}
}

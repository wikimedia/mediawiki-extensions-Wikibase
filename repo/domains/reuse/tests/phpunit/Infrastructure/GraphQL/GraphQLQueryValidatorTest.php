<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL;

use Generator;
use GraphQL\Language\AST\DocumentNode;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\GraphQLErrorType;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLQueryValidator;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Validation\InvalidResult;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Validation\ValidResult;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLQueryValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GraphQLQueryValidatorTest extends TestCase {

	/** @dataProvider validQueryProvider */
	public function testValidQueryReturnsDocumentNode( string $query ): void {
		$result = GraphQLQueryValidator::validate( $query );

		$this->assertInstanceOf( ValidResult::class, $result );
		$this->assertInstanceOf( DocumentNode::class, $result->documentNode );
	}

	public function validQueryProvider(): Generator {
		yield 'simple field query' => [ '{ item(id: "Q1") { id } }' ];
		yield 'named operation' => [ 'query GetItem { item(id: "Q42") { id } }' ];
	}

	/** @dataProvider missingQueryProvider */
	public function testMissingQueryIsInvalid( string $query ): void {
		$result = GraphQLQueryValidator::validate( $query );

		$this->assertInstanceOf( InvalidResult::class, $result );
		$this->assertSame( GraphQLErrorType::MISSING_QUERY->name, $result->errorType );
		$this->assertSame( "The 'query' field is required and must not be empty", $result->errorResponse['errors'][0]['message'] );
	}

	public function missingQueryProvider(): Generator {
		yield 'empty string' => [ '' ];
		yield 'whitespace only' => [ '   ' ];
		yield 'newline only' => [ "\n" ];
	}

	/** @dataProvider syntaxErrorQueryProvider */
	public function testSyntacticallyInvalidQueryIsInvalid( string $query ): void {
		$result = GraphQLQueryValidator::validate( $query );

		$this->assertInstanceOf( InvalidResult::class, $result );
		$this->assertSame( GraphQLErrorType::INVALID_QUERY->name, $result->errorType );
		$this->assertStringStartsWith( 'Invalid query - ', $result->errorResponse['errors'][0]['message'] );
	}

	public function syntaxErrorQueryProvider(): Generator {
		yield 'missing closing brace' => [ '{ item(id: "Q1") { id }' ];
		yield 'missing opening brace' => [ 'item(id: "Q1") { id } }' ];
	}

}

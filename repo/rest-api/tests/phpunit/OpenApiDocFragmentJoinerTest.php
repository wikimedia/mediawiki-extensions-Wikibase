<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Wikibase\Repo\RestApi\OpenApiDocFragmentJoiner;

/**
 * @covers \Wikibase\Repo\RestApi\OpenApiDocFragmentJoiner
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class OpenApiDocFragmentJoinerTest extends TestCase {

	private const BASE_DOC = [
		'openapi' => '3.1.0',
		'info' => [ 'title' => 'Wikibase REST API', 'version' => '1.0' ],
		'tags' => [ [ 'name' => 'items', 'description' => 'Wikibase Items' ] ],
		'paths' => [
			'/v1/entities/items/{item_id}' => [ 'get' => [ 'operationId' => 'getItem' ] ],
		],
		'components' => [ 'responses' => [ 'ResourceNotFound' => [ 'description' => 'not found' ] ] ],
	];

	public function testJoinsRoutableFragmentPathsAndTags(): void {
		$examplePath = [ 'get' => [ 'operationId' => 'getExample' ] ];
		$fragment = [
			'openapi' => '3.1.0',
			'info' => [ 'title' => 'fragment', 'version' => '0.1' ],
			'tags' => [ [ 'name' => 'examples', 'description' => 'Example resources' ] ],
			'paths' => [
				'/v0/examples/{example_id}' => $examplePath,
				'/v0/examples/{example_id}/parts' => [ 'get' => [ 'operationId' => 'getExampleParts' ] ],
			],
			'components' => [ 'responses' => [ 'SomethingElse' => [ 'description' => 'unused' ] ] ],
		];

		$joiner = $this->newJoiner( [ '/v0/examples/{example_id}' ] );
		$joiner->join( $fragment, 'ExampleExtension' );
		$joined = json_decode( $joiner->getDocumentJson(), true );

		// the routable path is joined, the non-routable one is not
		$this->assertSame(
			[ '/v1/entities/items/{item_id}', '/v0/examples/{example_id}' ],
			array_keys( $joined['paths'] )
		);
		$this->assertSame( $examplePath, $joined['paths']['/v0/examples/{example_id}'] );
		$this->assertSame( [ 'items', 'examples' ], array_column( $joined['tags'], 'name' ) );

		// everything but paths and tags is the base document's, untouched by the fragment
		$this->assertSame( self::BASE_DOC['info'], $joined['info'] );
		$this->assertSame( self::BASE_DOC['components'], $joined['components'] );
	}

	public function testGivenNothingJoined_documentIsByteIdenticalToBase(): void {
		$joiner = $this->newJoiner( [ '/v0/examples/{example_id}' ] );

		$this->assertSame( self::baseJson(), $joiner->getDocumentJson() );
	}

	public function testGivenFragmentWithoutRoutablePaths_contributesNothingTagsIncluded(): void {
		$joiner = $this->newJoiner( [] );
		$joiner->join( [
			'tags' => [ [ 'name' => 'examples', 'description' => 'Example resources' ] ],
			'paths' => [ '/v0/examples/{example_id}' => [] ],
		], 'ExampleExtension' );

		$this->assertSame( self::baseJson(), $joiner->getDocumentJson() );
	}

	public function testDoesNotDuplicateExistingTag(): void {
		$joiner = $this->newJoiner( [ '/v0/examples/{example_id}' ] );
		$joiner->join( [
			'tags' => [ [ 'name' => 'items', 'description' => 'redefined items tag' ] ],
			'paths' => [ '/v0/examples/{example_id}' => [] ],
		], 'ExampleExtension' );

		$this->assertSame( self::BASE_DOC['tags'], json_decode( $joiner->getDocumentJson(), true )['tags'] );
	}

	public function testGivenFragmentRedefinesBasePath_throws(): void {
		$joiner = $this->newJoiner( [ '/v1/entities/items/{item_id}' ] );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage(
			"OpenAPI doc fragment 'ExampleExtension' redefines path '/v1/entities/items/{item_id}'"
		);

		$joiner->join( [ 'paths' => [ '/v1/entities/items/{item_id}' => [] ] ], 'ExampleExtension' );
	}

	public function testGivenTwoFragmentsDefineSamePath_throws(): void {
		$fragment = [ 'paths' => [ '/v0/examples/{example_id}' => [] ] ];
		$joiner = $this->newJoiner( [ '/v0/examples/{example_id}' ] );
		$joiner->join( $fragment, 'FirstExtension' );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage(
			"OpenAPI doc fragment 'SecondExtension' redefines path '/v0/examples/{example_id}'"
		);

		$joiner->join( $fragment, 'SecondExtension' );
	}

	private function newJoiner( array $routablePaths ): OpenApiDocFragmentJoiner {
		return new OpenApiDocFragmentJoiner( self::baseJson(), $routablePaths );
	}

	private static function baseJson(): string {
		return json_encode( self::BASE_DOC );
	}

}

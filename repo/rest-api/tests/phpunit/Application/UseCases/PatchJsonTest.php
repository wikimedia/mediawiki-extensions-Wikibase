<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchJson
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchJsonTest extends TestCase {

	public function testSuccess(): void {
		$serialization = [
			'foo' => [ 'bar' => 'some', 'zip' => 'thing' ],
			'baz' => [ 1, 2, 3 ],
			'bat' => false,
			'snap' => 'crackle',
		];
		$patch = [
			[ 'op' => 'add', 'path' => '/baz/-', 'value' => '4' ],
			[ 'op' => 'replace', 'path' => '/foo/zip', 'value' => 'zap' ],
			[ 'op' => 'remove', 'path' => '/snap' ],
		];

		$this->assertSame(
			[ 'foo' => [ 'bar' => 'some', 'zip' => 'zap' ], 'baz' => [ 1, 2, 3, '4' ], 'bat' => false ],
			(array)$this->newPatchJson()->execute( $serialization, $patch )
		);
	}

	/**
	 * @dataProvider provideInapplicablePatch
	 */
	public function testGivenValidInapplicablePatch_throws(
		array $patch,
		string $expectedErrorCode,
		array $expectedContext
	): void {
		try {
			$serialization = [ 'foo' => 'bar', 'some' => 'value' ];

			$this->newPatchJson()->execute( $serialization, $patch );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedErrorCode, $e->getErrorCode() );
			$this->assertEquals( $expectedContext, $e->getErrorContext() );
		}
	}

	public static function provideInapplicablePatch(): Generator {
		$patchOperation = [ 'op' => 'remove', 'path' => '/path/does/not/exist' ];
		yield 'non-existent path' => [
			[ $patchOperation ],
			UseCaseError::PATCH_TARGET_NOT_FOUND,
			[ 'operation' => $patchOperation, 'field' => 'path' ],
		];

		$patchOperation = [ 'op' => 'copy', 'from' => '/path/does/not/exist', 'path' => '/baz' ];
		yield 'non-existent from' => [
			[ $patchOperation ],
			UseCaseError::PATCH_TARGET_NOT_FOUND,
			[ 'operation' => $patchOperation, 'field' => 'from' ],
		];

		$patchOperation = [ 'op' => 'test', 'path' => '/some', 'value' => 'incorrect value' ];
		yield 'patch test operation failed' => [
			[ $patchOperation ],
			UseCaseError::PATCH_TEST_FAILED,
			[ 'operation' => $patchOperation, 'actual-value' => 'value' ],
		];
	}

	private function newPatchJson(): PatchJson {
		return new PatchJson( new JsonDiffJsonPatcher() );
	}

}

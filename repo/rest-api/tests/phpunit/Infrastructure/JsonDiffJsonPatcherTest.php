<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use Exception;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Domain\Exceptions\PatchPathException;
use Wikibase\Repo\RestApi\Domain\Exceptions\PatchTestConditionFailedException;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class JsonDiffJsonPatcherTest extends TestCase {

	/**
	 * @dataProvider validPatchProvider
	 */
	public function testPatch( array $target, array $patch, array $expected ): void {
		$result = ( new JsonDiffJsonPatcher() )->patch( $target, $patch );

		$this->assertEquals( $expected, $result );
	}

	public function validPatchProvider(): Generator {
		yield 'add a field' => [
			[ 'foo' => 'bar', 'baz' => 42 ],
			[ [ 'op' => 'add', 'path' => '/new', 'value' => 'value' ] ],
			[ 'foo' => 'bar', 'baz' => 42, 'new' => 'value' ],
		];

		yield 'replace a field' => [
			[ 'foo' => 'bar', 'baz' => 42 ],
			[ [ 'op' => 'replace', 'path' => '/foo', 'value' => 'patched' ] ],
			[ 'foo' => 'patched', 'baz' => 42 ],
		];

		yield 'remove a field' => [
			[ 'foo' => 'bar', 'baz' => 42 ],
			[ [ 'op' => 'remove', 'path' => '/foo' ] ],
			[ 'baz' => 42 ],
		];

		yield 'test a field value' => [
			[ 'foo' => 'bar', 'baz' => 42 ],
			[ [ 'op' => 'test', 'path' => '/baz', 'value' => 42 ] ],
			[ 'foo' => 'bar', 'baz' => 42 ],
		];
	}

	/**
	 * @dataProvider invalidPatchProvider
	 */
	public function testGivenInvalidPatch_throwsException( array $patch ): void {
		$this->expectException( InvalidArgumentException::class );

		( new JsonDiffJsonPatcher() )->patch( [ 'potato' => 'chips' ], $patch );
	}

	public function invalidPatchProvider(): Generator {
		yield 'patch operation is not an array' => [
			[ 'potato' ],
		];

		yield 'invalid patch operation op' => [
			[ [ 'op' => 'boil', 'path' => '/potato' ] ],
		];
	}

	public function testGivenPatchTestConditionFailed_throwsException(): void {
		$testOperation = [ 'op' => 'test', 'path' => '/foo/bar', 'value' => 'baz' ];

		try {
			( new JsonDiffJsonPatcher() )->patch( [ 'foo' => [ 'bar' => 42 ] ], [ $testOperation ] );
		} catch ( PatchTestConditionFailedException $exception ) {
			$this->assertEquals( $testOperation, $exception->getOperation() );
			$this->assertEquals( 42, $exception->getActualValue() );
		}
	}

	public function testGivenInvalidPatchOperationPath_throwsException(): void {
		$operation = [ 'op' => 'remove', 'path' => '/field/does/not/exist' ];

		try {
			( new JsonDiffJsonPatcher() )->patch( [ 'foo' => 'bar' ], [ $operation ] );
		} catch ( Exception $exception ) {
			$this->assertInstanceOf( PatchPathException::class, $exception );
			$this->assertSame( 'path', $exception->getField() );
			$this->assertSame( $operation, $exception->getOperation() );
		}
	}

}

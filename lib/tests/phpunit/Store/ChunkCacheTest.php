<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\Lib\Store\ChunkAccess;
use Wikibase\Lib\Store\ChunkCache;

/**
 * @covers \Wikibase\Lib\Store\ChunkCache
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ChunkCacheTest extends \MediaWikiTestCase {

	protected static function getTestData() {
		static $data = [];

		if ( empty( $data ) ) {
			for ( $i = 0; $i < 100; $i++ ) {
				$data[$i] = strval( $i );
			}
		}

		return $data;
	}

	/**
	 * Instruct the test to perform a cache access.
	 *
	 * @param int $start The start address of the chunk to load.
	 * @param int $length The length of the chunk to load.
	 * @param array $expectedAccess List of [ $start, $length ] accesses to the backing store.
	 * @param string $info Description.
	 * @return array
	 */
	protected static function makeCacheAction( $start, $length, array $expectedAccess, $info ): array {
		$data = self::getTestData();

		return [
			'start' => $start,
			'length' => $length,
			'expected' => array_slice( $data, $start, $length ),
			'expectedAccess' => $expectedAccess,
			'info' => $info,
		];
	}

	public function provideLoadChunk() {
		return [
			'basic loading' => [
				10,  // chunkSize
				50, // maxSize
				[
					self::makeCacheAction( 0, 4, [ [ 0, 10 ] ], 'start at the start' ),
					self::makeCacheAction( 10, 4, [ [ 10, 10 ] ], 'start at ten' ),
					self::makeCacheAction( 98, 5, [ [ 98, 10 ] ], 'exceed end' ),
				]
			],

			'matching & loading' => [
				4,  // chunkSize
				50, // maxSize
				[
					self::makeCacheAction( 20, 4, [ [ 20, 4 ] ], 'start in the middle' ),

					self::makeCacheAction( 16, 4,  [ [ 16, 4 ] ], 'fit block before' ),
					self::makeCacheAction( 24, 4,  [ [ 24, 4 ] ], 'fit block after' ),

					self::makeCacheAction( 14, 4, [ [ 14, 2 ] ], 'overlap block before' ),
					self::makeCacheAction( 26, 4, [ [ 28, 4 ] ], 'overlap block after' ),

					self::makeCacheAction( 7, 4,  [ [ 7, 4 ] ], 'detached block before' ),
					self::makeCacheAction( 33, 4,  [ [ 33, 4 ] ], 'detached block after' ),

					self::makeCacheAction( 21, 2,  [], 'single chunk match' ),
					self::makeCacheAction( 18, 8,  [], 'multi chunk match' ),
				]
			],

			'pruning' => [
				3, // chunkSize
				7, // maxSize
				[
					self::makeCacheAction( 3, 3, [ [ 3, 3 ] ], 'first chunk fits' ),
					self::makeCacheAction( 0, 3, [ [ 0, 3 ] ], 'second chunk fits' ),
					self::makeCacheAction( 2, 4, [], 'third chunk is a hit' ),
					self::makeCacheAction( 16, 4, [ [ 16, 3 ], [ 19, 3 ] ], 'fourth chunk triggers prune' ),
					self::makeCacheAction( 22, 4, [ [ 22, 3 ], [ 25, 3 ] ], 'fifth chunk triggers prune' ),
					self::makeCacheAction( 26, 4, [ [ 28, 3 ] ], 'sixth chunk triggers prune' ),
					self::makeCacheAction( 2, 4, [ [ 2, 3 ], [ 5, 3 ] ], 'third chunk is no longer a hit' ),
				]
			],

		];
	}

	/**
	 * @dataProvider provideLoadChunk
	 */
	public function testLoadChunk( $chunkSize, $maxSize, $sequence ) {
		$data = self::getTestData();

		$realStore = new MockChunkAccess( $data );
		$store = $this->getMock( ChunkAccess::class );
		$store->method( 'loadChunk' )
			->withConsecutive( ...array_merge( ...array_map(
				function ( $action ) {
					return $action['expectedAccess'];
				},
				$sequence
			) ) )
			->willReturnCallback( [ $realStore, 'loadChunk' ] );
		$store->method( 'getRecordId' )
			->willReturnCallback( [ $realStore, 'getRecordId' ] );
		$cache = new ChunkCache( $store, $chunkSize, $maxSize );

		foreach ( $sequence as $action ) {
			$start = $action['start'];
			$length = $action['length'];
			$expected = $action['expected'];
			$info = $action['info'];

			$chunk = $cache->loadChunk( $start, $length );
			$this->assertEquals( $expected, $chunk, $info );
		}
	}

	/**
	 * Fuzz test for discovering unexpected issues
	 */
	public function testFuzz() {
		$data = self::getTestData();

		$store = new MockChunkAccess( $data );
		$cache = new ChunkCache( $store, 10, 50 );

		for ( $i = 0; $i < 100; $i++ ) {
			$start = mt_rand( 0, 110 );
			$length = mt_rand( 1, 20 );
			$expected = array_slice( $data, $start, $length );
			$info = "fuzz: start $start, len $length";

			$chunk = $cache->loadChunk( $start, $length );
			$this->assertEquals( $expected, $chunk, $info );
		}
	}

}

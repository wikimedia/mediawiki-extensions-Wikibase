<?php

namespace Wikibase\Lib\Tests\Store;

use MediaWikiIntegrationTestCase;
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
class ChunkCacheTest extends MediaWikiIntegrationTestCase {

	/**
	 * @return string[]
	 */
	private function getTestData() {
		static $data = [];

		if ( $data === [] ) {
			$data = array_map( 'strval', range( 0, 99 ) );
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
	private function makeCacheAction( $start, $length, array $expectedAccess, $info ): array {
		$data = $this->getTestData();

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
					$this->makeCacheAction( 0, 4, [ [ 0, 10 ] ], 'start at the start' ),
					$this->makeCacheAction( 10, 4, [ [ 10, 10 ] ], 'start at ten' ),
					$this->makeCacheAction( 98, 5, [ [ 98, 10 ] ], 'exceed end' ),
				]
			],

			'matching & loading' => [
				4,  // chunkSize
				50, // maxSize
				[
					$this->makeCacheAction( 20, 4, [ [ 20, 4 ] ], 'start in the middle' ),

					$this->makeCacheAction( 16, 4,  [ [ 16, 4 ] ], 'fit block before' ),
					$this->makeCacheAction( 24, 4,  [ [ 24, 4 ] ], 'fit block after' ),

					$this->makeCacheAction( 14, 4, [ [ 14, 2 ] ], 'overlap block before' ),
					$this->makeCacheAction( 26, 4, [ [ 28, 4 ] ], 'overlap block after' ),

					$this->makeCacheAction( 7, 4,  [ [ 7, 4 ] ], 'detached block before' ),
					$this->makeCacheAction( 33, 4,  [ [ 33, 4 ] ], 'detached block after' ),

					$this->makeCacheAction( 21, 2,  [], 'single chunk match' ),
					$this->makeCacheAction( 18, 8,  [], 'multi chunk match' ),
				]
			],

			'pruning' => [
				3, // chunkSize
				7, // maxSize
				[
					// note that length-4 access chunks below are actually two cache chunks
					$this->makeCacheAction( 3, 3, [ [ 3, 3 ] ], 'first chunk fits' ),
					$this->makeCacheAction( 0, 3, [ [ 0, 3 ] ], 'second chunk fits' ),
					$this->makeCacheAction( 2, 4, [], 'third chunk hits' ),
					$this->makeCacheAction( 16, 4, [ [ 16, 3 ], [ 19, 3 ] ], 'fourth chunk evicts first+second' ),
					$this->makeCacheAction( 22, 4, [ [ 22, 3 ], [ 25, 3 ] ], 'fifth chunk evicts fourth' ),
					$this->makeCacheAction( 22, 4, [], 'fifth chunk hits' ),
					$this->makeCacheAction( 16, 4, [ [ 16, 3 ], [ 19, 3 ] ], 'fourth chunk evicts fifth' ),
					$this->makeCacheAction( 22, 4, [ [ 22, 3 ], [ 25, 3 ] ], 'fifth chunk evicts fourth' ),
					$this->makeCacheAction( 26, 4, [ [ 28, 3 ] ], 'sixth chunk evicts fifth' ),
					$this->makeCacheAction( 2, 4, [ [ 2, 3 ], [ 5, 3 ] ], 'third chunk misses' ),
					$this->makeCacheAction( 2, 4, [], 'third chunk hits' ),
				]
			],

		];
	}

	/**
	 * @dataProvider provideLoadChunk
	 */
	public function testLoadChunk( $chunkSize, $maxSize, $sequence ) {
		$data = $this->getTestData();

		$realStore = new MockChunkAccess( $data );
		$store = $this->createMock( ChunkAccess::class );
		$store->method( 'loadChunk' )
			->withConsecutive( ...array_merge( ...array_column( $sequence, 'expectedAccess' ) ) )
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
		$data = $this->getTestData();

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

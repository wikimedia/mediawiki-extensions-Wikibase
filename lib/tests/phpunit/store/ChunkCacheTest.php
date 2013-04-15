<?php
namespace Wikibase\Test;

use Wikibase\ChunkCache;

/**
 * Tests for the Wikibase\ChunkCache class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */

class ChunkCacheTest extends \MediaWikiTestCase {

	protected static function getTestData() {
		static $data = array();

		if ( empty( $data ) ) {
			for ( $i = 0; $i < 100; $i++ ) {
				$data[$i] = strval( $i );
			}
		}

		return $data;
	}

	protected static function makeCacheAction( $start, $length, $info ) {
		$data = self::getTestData();

		return array(
			'start' => $start,
			'length' => $length,
			'expected' => array_slice( $data, $start, $length ),
			'info' => $info
		);
	}

	public static function provideLoadChunk() {
		return array(
			array( // #0: basic loading
				10,  // chunkSize
				50, // maxSize
				array(
					self::makeCacheAction(  0, 4, 'start at the start' ),
					self::makeCacheAction( 10, 4, 'start at ten' ),
					self::makeCacheAction( 98, 5, 'exceed end' ),
				)
			),

			array( // #1: matching & loading
				10,  // chunkSize
				50, // maxSize
				array(
					self::makeCacheAction( 20, 4, 'start in the middle' ),

					self::makeCacheAction( 16, 4, 'fit block before' ),
					self::makeCacheAction( 24, 4, 'fit block after' ),

					self::makeCacheAction( 14, 4, 'overlap block before' ),
					self::makeCacheAction( 26, 4, 'overlap block after' ),

					self::makeCacheAction(  7, 4, 'detached block before' ),
					self::makeCacheAction( 33, 4, 'detached block after' ),

					self::makeCacheAction( 21, 2, 'single chunk match' ),
					self::makeCacheAction( 18, 8, 'multi chunk match' ),
				)
			),

			array( // #2: pruning
				3, // chunkSize
				7, // maxSize
				array(
					self::makeCacheAction( 3, 3, 'first chunk fits' ),
					self::makeCacheAction( 0, 3, 'second chunk fits' ),
					self::makeCacheAction( 2, 4, 'third chunk is a hit' ),
					self::makeCacheAction( 16, 4, 'fourth chunk triggers prune' ),
					self::makeCacheAction( 22, 4, 'fifth chunk triggers prune' ),
					self::makeCacheAction( 26, 4, 'sixth chunk triggers prune' ),
				)
			),

		);
	}

	/**
	 * @dataProvider provideLoadChunk
	 */
	public function testLoadChunk( $chunkSize, $maxSize, $sequence ) {
		$data = self::getTestData();

		$store = new MockChunkAccess( $data );
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
			$expected =  array_slice( $data, $start, $length );
			$info = "fuzz: start $start, len $length";

			$chunk = $cache->loadChunk( $start, $length );
			$this->assertEquals( $expected, $chunk, $info );
		}
	}
}
<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\Lib\Store\ChunkAccess;

/**
 * Mock implementation of the ChunkAccess interface
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class MockChunkAccess implements ChunkAccess {

	protected $data;

	public function __construct( $data ) {
		$this->data = $data;
	}

	/**
	 * Returns a chunk as a list of whatever object is used for data records by
	 * the implementing class.
	 *
	 * The present implementation is quite inefficient at O(n).
	 *
	 * @param int $start The first ID in the chunk
	 * @param int $size  The desired size of the chunk
	 *
	 * @return array the desired chunk of rows/objects
	 */
	public function loadChunk( $start, $size ) {
		reset( $this->data );
		do {
			$rec = current( $this->data );

			if ( $rec === false ) {
				break;
			}

			$id = $this->getRecordId( $rec );

			if ( $id >= $start ) {
				break;
			}
		} while ( next( $this->data ) );

		$c = 0;
		$chunk = [];
		do {
			if ( $c >= $size ) {
				break;
			}

			$rec = current( $this->data );

			if ( $rec === false ) {
				break;
			}

			$chunk[] = $rec;
			$c++;
		} while ( next( $this->data ) );

		return $chunk;
	}

	/**
	 * Returns the sequential ID of the given data record.
	 *
	 * @param mixed $rec
	 *
	 * @return int
	 */
	public function getRecordId( $rec ) {
		return (int)$rec;
	}

}

<?php

namespace Wikibase\Lib\Store;

/**
 * Interface for DAO objects providing chunked access based on sequential indexes.
 * "holes" in the index sequence are acceptable but should not be frequent.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
interface ChunkAccess {

	/**
	 * Returns a chunk as a list of whatever object is used for data records by
	 * the implementing class.
	 *
	 * @param int $start The first ID in the chunk
	 * @param int $size  The desired size of the chunk
	 *
	 * @return array the desired chunk of rows/objects
	 */
	public function loadChunk( $start, $size );

	/**
	 * Returns the sequential ID of the given data record.
	 *
	 * @param mixed $rec
	 *
	 * @return int
	 */
	public function getRecordId( $rec );

}

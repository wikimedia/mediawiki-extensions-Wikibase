<?php

namespace Wikibase\Lib\Store;

use MWException;

/**
 * Interface for DAO objects providing chunked access.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class ChunkCache implements ChunkAccess {

	/**
	 * @var ChunkAccess
	 */
	private $source;

	/**
	 * Array containing cache entries; each entry is an associative array with the
	 * following fields:
	 *
	 * - start: the chunk's data (an array of data records)
	 * - next:  the next ID after the records in this chunk
	 * - data:  an array of data records
	 *
	 * The entries array is maintained sorted by the 'start' field of its entries.
	 *
	 * @var array
	 */
	private $entries = [];

	/**
	 * @var int
	 */
	private $size = 0;

	/**
	 * @var int
	 */
	private $maxSize;

	/**
	 * @var int
	 */
	private $chunkSize;

	/**
	 * @var int
	 */
	private $hitCount = 0;

	/**
	 * @var int
	 */
	private $missCount = 0;

	/**
	 * modification counter (logical clock)
	 *
	 * @var int
	 */
	private $modCount = 0;

	/**
	 * @param ChunkAccess $source    The source to load from
	 * @param int         $chunkSize The size of the chunk to load, if we have a choice
	 * @param int         $maxSize   The maximum size this cache can grow to
	 *
	 * @throws MWException
	 */
	public function __construct( ChunkAccess $source, $chunkSize, $maxSize ) {
		$this->source = $source;
		$this->maxSize = $maxSize;
		$this->chunkSize = $chunkSize;

		if ( $this->maxSize < $this->chunkSize ) {
			throw new MWException( "chunk size must be smaller than total max size" );
		}
	}

	/**
	 * Finds the position for the given key in the internal entry array.
	 * This is implemented using iterative binary search.
	 *
	 * @param int $key
	 *
	 * @return int the position if found, or the negative insert position minus one, if not.
	 */
	private function findEntryPosition( $key ) {
		if ( empty( $this->entries ) ) {
			return -1;
		}

		$low = 0;
		$high = count( $this->entries ) - 1;

		$bottom = $this->entries[$low];
		$top = $this->entries[$high];

		if ( $key < $bottom['start'] ) {
			return -1;
		}

		if ( $key >= $top['next'] ) {
			return -$high - 2;
		}

		while ( $low <= $high ) {
			assert( $high >= 0 );
			assert( $low >= 0 );

			$mid = (int)( ( $low + $high ) / 2 );

			$entry = $this->entries[$mid];

			if ( $key < $entry['start'] ) {
				$high = $mid - 1;
			} elseif ( $key >= $entry['next'] ) {
				$low = $mid + 1;
			} else {
				return $mid;
			}
		}

		// not found
		return -$low - 1;
	}

	/**
	 * Returns a chunk as a list of whatever object is used for data records by
	 * the implementing class.
	 *
	 * @param int $start The first ID in the chunk
	 * @param int $size  The desired size of the chunk
	 *
	 * @return array the desired chunk of rows/objects
	 */
	public function loadChunk( $start, $size ) {
		$result = [];
		$remaining = $size;

		while ( $remaining > 0 ) {
			$maxPos = count( $this->entries ) - 1;
			$pos = $this->findEntryPosition( $start );

			if ( $pos >= 0 ) {
				// the desired start key is cached

				$entry = $this->entries[ $pos ];
				$this->entries[ $pos ]['touched'] = ++$this->modCount; // bump

				$hit = true;
			} else {
				// the desired start key is not cached

				$ipos = -$pos - 1; // insert position

				if ( $ipos <= $maxPos && $maxPos >= 0 ) {
					// we are inserting before an existing entry, so clip the size.

					$next = $this->entries[ $ipos ];
					assert( $start < $next['start'] );

					$partSize = min( $this->chunkSize, $next['start'] - $start );
				} else {
					// we are inserting after the last cache entry, load as much as we can.

					$partSize = $this->chunkSize;
				}

				$entry = $this->insertChunk( $start, $partSize, $ipos );

				if ( !$entry ) {
					// nothing could be loaded, perhaps old records got pruned?
					// If we are < $maxPos, we could advance $start by 1 and try again...
					break;
				}

				$hit = false;
			}

			$offset = $start - $entry['start']; // offset inside the cached data

			$part = array_slice( $entry['data'], $offset, $remaining );
			$partSize = count( $part );
			$result = array_merge( $result, $part );

			// update start and remaining
			$start = $entry['next'];
			$remaining -= $partSize;

			if ( $hit ) {
				$this->hitCount += $partSize;
			} else {
				$this->missCount += $partSize;
			}
		}

		return $result;
	}

	/**
	 * @param int $start the ID to start loading at
	 * @param int $size the maximum size of the chunk to load
	 * @param int $before insert into the internal entry list before this position.
	 *
	 * @throws MWException
	 * @return array|bool the cache entry created by inserting the new chunk, or false if
	 *         there is no more data to load from the source at the given position.
	 *         The cache entry is an associative array containing the following keys:
	 *         - start: the key the chunk starts at
	 *         - data:  a list of data records
	 *         - next:  the id the following chunk starts at (or after)
	 *         - touched: (logical) timestamp of the entry's creation (taken from $this->modCount)
	 */
	private function insertChunk( $start, $size, $before ) {
		if ( !is_int( $start ) || !is_int( $size ) || !is_int( $before )
			|| $start < 0 || $size < 0 || $before < 0
		) {
			throw new MWException( '$start, $size and $before must be non-negative integers.' );
		}

		$data = $this->source->loadChunk( $start, $size );

		if ( empty( $data ) ) {
			return false;
		}

		$last = end( $data );

		$next = $this->source->getRecordId( $last ) + 1;

		reset( $data );

		$entry = [
			'start' => $start,
			'data' => $data,
			'next' => $next,
			'touched' => ++$this->modCount,
		];

		$this->entries = array_merge(
			array_slice( $this->entries, 0, $before ),
			[ $entry ],
			array_slice( $this->entries, $before )
		);

		$this->size += count( $data );

		$this->prune();

		return $entry;
	}

	/**
	 * Removes least recently used chunks until the total size is smaller than the max size
	 * specified in the constructor.
	 *
	 * Note that this implementation is rather inefficient for large number of chunks.
	 */
	private function prune() {
		if ( $this->size <= $this->maxSize ) {
			return;
		}

		$lru = $this->entries; // copy (PHP is crazy like that)
		usort( $lru,
			function ( $a, $b ) {
				return $a['touched'] - $b['touched'];
			}
		);

		while ( $this->size > $this->maxSize && !empty( $this->entries ) ) {
			$entry = array_shift( $lru );

			$this->dropChunk( $entry['start'] );
		}
	}

	/**
	 * Remove the chunk with the given start key from the cache.
	 * Used during pruning.
	 *
	 * @param int $startKey
	 *
	 * @return bool
	 */
	private function dropChunk( $startKey ) {
		foreach ( $this->entries as $pos => $entry ) {
			if ( $entry['start'] === $startKey ) {
				unset( $this->entries[$pos] );

				// re-index
				$this->entries = array_values( $this->entries );
				$this->size -= count( $entry['data'] );

				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the sequential ID of the given data record.
	 *
	 * @param mixed $rec
	 *
	 * @return int
	 */
	public function getRecordId( $rec ) {
		return $this->source->getRecordId( $rec );
	}

}

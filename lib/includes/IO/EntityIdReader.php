<?php

namespace Wikibase\IO;

use Disposable;
use Iterator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\EntityIdParser;

/**
 * EntityIdReader reads entity IDs from a file, one per line.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class EntityIdReader implements Iterator, Disposable {

	/**
	 * @var LineReader
	 */
	protected $reader;

	/**
	 * @param resource $fileHandle The file to read from.
	 * @param bool $canClose Whether calling dispose() should close the fine handle.
	 * @param bool $autoDispose Whether to automatically call dispose() when reaching EOF.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( $fileHandle, $canClose = true, $autoDispose = false ) {
		$this->reader = new LineReader( $fileHandle, $canClose, $autoDispose );
		$this->parser = new EntityIdParser(); //TODO: inject?
	}

	/**
	 * @param string $line
	 * @return EntityId
	 */
	protected function lineToId( $line ) {
		$line = trim( $line );
		$id = $this->parser->parse( $line );
		//TODO: optionally catch, log & ignore ParseException

		return $id;
	}

	/**
	 */
	public function dispose() {
		$this->reader->dispose();
	}

	/**
	 * Returns the current ID.
	 *
	 * @link http://php.net/manual/en/iterator.current.php
	 * @return EntityId
	 */
	public function current() {
		$line = $this->reader->current();
		return $this->lineToId( $line );
	}

	/**
	 * Advance to next ID. Blank lines are skipped.
	 *
	 * @see LineReader::next()
	 */
	public function next() {
		do {
			$this->reader->next();
		} while ( $this->reader->valid() && trim( $this->reader->current() ) === '' );
	}

	/**
	 * @see LineReader::key()
	 * @return int
	 */
	public function key() {
		return $this->reader->key();
	}

	/**
	 * @see LineReader::valid()
	 * @return boolean
	 */
	public function valid() {
		return $this->reader->valid();
	}

	/**
	 * @see LineReader::rewind()
	 */
	public function rewind() {
		$this->reader->rewind();
	}

}
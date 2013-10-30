<?php

namespace Wikibase\IO;

use Disposable;
use ExceptionHandler;
use Iterator;
use ValueParsers\ParseException;
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
	 * @var ExceptionHandler
	 */
	protected $exceptionHandler;

	/**
	 * @var EntityId|null
	 */
	protected $current = null;

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

		$this->exceptionHandler = new \RethrowingExceptionHandler();
	}

	/**
	 * @param \ExceptionHandler $exceptionHandler
	 */
	public function setExceptionHandler( $exceptionHandler ) {
		$this->exceptionHandler = $exceptionHandler;
	}

	/**
	 * @return \ExceptionHandler
	 */
	public function getExceptionHandler() {
		return $this->exceptionHandler;
	}

	/**
	 * @param string $line
	 * @return EntityId|null
	 */
	protected function lineToId( $line ) {
		$line = trim( $line );

		try {
			$id = $this->parser->parse( $line );
		} catch ( ParseException $ex ) {
			$this->exceptionHandler->handleException( $ex, 'bad-entity-id', "Failed to parse Entity ID $line" );
			$id = null;
		}

		return $id;
	}

	/**
	 * Closes the underlying input stream
	 */
	public function dispose() {
		$this->reader->dispose();
	}

	/**
	 * Returns the current ID, or, if that has been consumed, finds the next ID on
	 * the input stream and return it.
	 *
	 * @return EntityId|null
	 */
	protected function fill() {
		while ( $this->current === null && $this->reader->valid() ) {
			$line = trim( $this->reader->current() );
			$this->reader->next();

			if ( $line === '' ) {
				continue;
			}

			$this->current = $this->lineToId( $line );
		};

		return $this->current;
	}

	/**
	 * Returns the current ID.
	 *
	 * @link http://php.net/manual/en/iterator.current.php
	 * @return EntityId
	 */
	public function current() {
		$id = $this->fill();
		return $id;
	}

	/**
	 * Advance to next ID. Blank lines are skipped.
	 *
	 * @see LineReader::next()
	 */
	public function next() {
		$this->current = null; // consume current
		$this->fill();
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
		return $this->fill() !== null;
	}

	/**
	 * @see LineReader::rewind()
	 */
	public function rewind() {
		$this->current = null;
		$this->reader->rewind();
	}

}
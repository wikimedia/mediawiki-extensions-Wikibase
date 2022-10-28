<?php

namespace Wikibase\Repo\IO;

use InvalidArgumentException;
use Iterator;
use LogicException;

/**
 * LineReader allows iterating over the lines of a file.
 * Each line returned will contain the line separator character(s) and all whitespace.
 * Concatenating all lines returned by the reader should result in the original file.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class LineReader implements Iterator {

	/**
	 * @var resource|null
	 */
	private $fileHandle;

	/**
	 * Whether dispose() will close the file handle.
	 *
	 * @var bool
	 */
	private $canClose;

	/**
	 * Whether dispose() is called automatically when the end of file is reached.
	 *
	 * @var bool
	 */
	private $autoDispose;

	/**
	 * @var string|null
	 */
	private $current = null;

	/**
	 * @var int
	 */
	private $line = 0;

	/**
	 * @param resource $fileHandle The file to read from.
	 * @param bool $canClose Whether calling dispose() should close the fine handle.
	 * @param bool $autoDispose Whether to automatically call dispose() when reaching EOF
	 *             or when this reader is destructed.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $fileHandle, $canClose = true, $autoDispose = false ) {
		if ( !$fileHandle ) {
			throw new InvalidArgumentException( '$fileHandle must be a file resource.' );
		}

		if ( !is_bool( $canClose ) ) {
			throw new InvalidArgumentException( '$canClose must be a boolean.' );
		}

		if ( !is_bool( $autoDispose ) ) {
			throw new InvalidArgumentException( '$autoDispose must be a boolean.' );
		}

		$this->fileHandle = $fileHandle;

		$this->canClose = $canClose;
		$this->autoDispose = $autoDispose;
	}

	/**
	 * Closes the underlying file handle if the $canClose parameter was given as
	 * true (the default) in the constructor.
	 */
	public function dispose() {
		if ( $this->fileHandle && $this->canClose ) {
			fclose( $this->fileHandle );
		}

		$this->fileHandle = null;
	}

	/**
	 * Destructor, calls dispose() if $autoDispose was set in the constructor.
	 */
	public function __destruct() {
		if ( $this->autoDispose ) {
			$this->dispose();
		}
	}

	/**
	 * Return the current line.
	 *
	 * @see http://php.net/manual/en/iterator.current.php
	 * @return string
	 */
	public function current(): string {
		if ( !$this->valid() ) {
			throw new LogicException( 'Current position is not valid' );
		}
		return $this->current;
	}

	/**
	 * Reads the next line. Use current() to get the line's content.
	 *
	 * @see http://php.net/manual/en/iterator.next.php
	 */
	public function next(): void {
		$this->current = fgets( $this->fileHandle );

		if ( $this->valid() ) {
			$this->line++;
		} elseif ( $this->autoDispose ) {
			$this->dispose();
		}
	}

	/**
	 * Return the current line number.
	 * @see http://php.net/manual/en/iterator.key.php
	 * @return int
	 */
	public function key(): int {
		return $this->line;
	}

	/**
	 * Checks if current position is valid. Returns true if and only if
	 * next() has been called at least once and the end of file has not yet been reached.
	 *
	 * @see http://php.net/manual/en/iterator.valid.php
	 * @return boolean whether there is a current line
	 */
	public function valid(): bool {
		return is_string( $this->current );
	}

	/**
	 * Sets the file pointer to the beginning of the file, if supported.
	 * Has no effect if this LineReader has already been disposed.
	 *
	 * @see http://php.net/manual/en/iterator.rewind.php
	 * @return void Any returned value is ignored.
	 */
	public function rewind(): void {
		if ( $this->fileHandle ) {
			fseek( $this->fileHandle, 0 );
			$this->current = null;

			$this->next();
		}
	}

}

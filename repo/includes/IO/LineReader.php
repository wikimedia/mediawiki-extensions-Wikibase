<?php

namespace Wikibase\Repo\IO;

use Disposable;
use Iterator;

/**
 * LineReader allows iterating over the lines of a file.
 * Each line returned will contain the line separator character(s) and all whitespace.
 * Concatenating all lines returned by the reader should result in the original file.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class LineReader implements Iterator, Disposable {

	/**
	 * @var resource
	 */
	protected $fileHandle;

	/**
	 * Whether dispose() will close the file handle.
	 *
	 * @var bool
	 */
	protected $canClose;

	/**
	 * Whether dispose() is called automatically when the end of file is reached.
	 *
	 * @var bool
	 */
	protected $autoDispose;

	/**
	 * @var string
	 */
	protected $current = null;

	/**
	 * @var int
	 */
	protected $line = 0;

	/**
	 * @param resource $fileHandle The file to read from.
	 * @param bool $canClose Whether calling dispose() should close the fine handle.
	 * @param bool $autoDispose Whether to automatically call dispose() when reaching EOF
	 *             or when this reader is destructed.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( $fileHandle, $canClose = true, $autoDispose = false ) {
		if ( !is_resource( $fileHandle ) ) {
			throw new \InvalidArgumentException( '$fileHandle must be a file resource.' );
		}

		if ( !is_bool( $canClose ) ) {
			throw new \InvalidArgumentException( '$canClose must be a boolean.' );
		}

		if ( !is_bool( $autoDispose ) ) {
			throw new \InvalidArgumentException( '$autoDispose must be a boolean.' );
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

		$this->fileHandle = false;
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
	 * @link http://php.net/manual/en/iterator.current.php
	 * @return string
	 */
	public function current() {
		return $this->current;
	}

	/**
	 * Reads the the next line. Use current() to get the line's content.
	 *
	 * @link http://php.net/manual/en/iterator.next.php
	 */
	public function next() {
		$this->current = fgets( $this->fileHandle );

		if ( $this->valid() ) {
			$this->line++;
		} elseif ( $this->autoDispose ) {
			$this->dispose();
		}
	}

	/**
	 * Return the current line number.
	 * @link http://php.net/manual/en/iterator.key.php
	 * @return int
	 */
	public function key() {
		return $this->line;
	}

	/**
	 * Checks if current position is valid. Returns true if and only if
	 * next() has been called at least once and the end of file has not yet been reached.
	 *
	 * @link http://php.net/manual/en/iterator.valid.php
	 * @return boolean whether there is a current line
	 */
	public function valid() {
		return is_string( $this->current );
	}

	/**
	 * Sets the file pointer to the beginning of the file, if supported.
	 * Has no effect if this LineReader has already been disposed.
	 *
	 * @link http://php.net/manual/en/iterator.rewind.php
	 * @return void Any returned value is ignored.
	 */
	public function rewind() {
		if ( $this->fileHandle ) {
			fseek( $this->fileHandle, 0 );
			$this->current = null;

			$this->next();
		}
	}
}

<?php

namespace Wikibase;
use Iterator;
use ResultWrapper;

/**
 * Base class for iterators that convert each row of a database result into an appropriate object.
 *
 * @since 0.5
 *
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 * @todo: this should implement Disposable know a LoadBalancer instance, so
 *        we can recycle the DB connection when done.
 */
abstract class ConvertingResultWrapper implements Iterator {

	/**
	 * @var \ResultWrapper
	 */
	protected $rows;

	/**
	 * @param ResultWrapper $rows
	 */
	public function __construct( ResultWrapper $rows ) {
		$this->rows = $rows;
	}

	/**
	 * @see Iterator::current()
	 * @see ResultWrapper::current()
	 */
	public function current() {
		$current = $this->rows->current();
		$current = $this->convert( $current );
		return $current;
	}

	/**
	 * @see Iterator::next()
	 * @see ResultWrapper::next()
	 */
	public function next() {
		$this->rows->next();
	}

	/**
	 * @see Iterator::key()
	 * @see ResultWrapper::key()
	 * @return mixed scalar or null
	 */
	public function key() {
		return $this->rows->key();
	}

	/**
	 * @see Iterator::valid()
	 * @see ResultWrapper::valid()
	 * @return bool
	 */
	public function valid() {
		return $this->rows->valid();
	}

	/**
	 * @see Iterator::rewind()
	 * @see ResultWrapper::rewind()
	 */
	public function rewind() {
		$this->rows->rewind();
	}

	/**
	 * Converts a database row into the desired representation.
	 *
	 * @param object $row An object representing the raw database row, as returned by ResultWrapper::current().
	 *
	 * @return mixed
	 */
	protected abstract function convert( $row );
}

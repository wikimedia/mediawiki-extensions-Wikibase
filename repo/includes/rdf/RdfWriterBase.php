<?php

namespace Wikibase\RDF;
use InvalidArgumentException;
use LogicException;

/**
 * Base class for RdfWriter implementations
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
abstract class RdfWriterBase implements RdfWriter {

	/**
	 * @var array An array of strings or RdfWriters.
	 */
	private $buffer = array();

	/**
	 * @var string the current state
	 */
	private $state = 'start';

	private $labeler;

	const DOCUMENT_ROLE = 'document';

	const BNODE_ROLE = 'bnode';

	const STATEMENT_ROLE = 'statement';

	/**
	 * @var string
	 */
	private $role;

	function __construct( $role, BNodeLabeler $labeler = null ) {
		if ( !is_string( $role ) ) {
			throw new InvalidArgumentException( '$role must be a string' );
		}

		$this->role = $role;

		$this->labeler = $labeler?: new BNodeLabeler();
	}

	/**
	 * @param string $role
	 * @param BNodeLabeler $labeler
	 *
	 * @return RdfWriterBase
	 */
	abstract protected function newSubWriter( $role, BNodeLabeler $labeler );

	/**
	 * @return RdfWriter
	 */
	final public function sub() {
		//FIXME: don't mess with the state, enqueue the writer to be placed in the buffer
		// later, on the next transtion to subject|document|drain
		$this->state( 'document' );

		$writer = $this->newSubWriter( self::DOCUMENT_ROLE, $this->labeler );
		$writer->state = 'document';

		$this->write( $writer );
		return $writer;
	}

	/**
	 * @return string a string corresponding to one of the the XXX_ROLE constants.
	 */
	final public function getRole() {
		return $this->role;
	}

	final protected function write() {
		$args = func_get_args();

		foreach ( $args as $s ) {
			$this->buffer[] = $s;
		}
	}

	/**
	 * @param string|null $label node label, will be generated if not given.
	 *
	 * @return string
	 */
	final public function blank( $label = null ) {
		return $this->labeler->getLabel( $label );
	}

	/**
	 * Emit a document header. Must be paired with a later call to finish().
	 */
	final public function start() {
		$this->state( 'document' );
	}

	/**
	 * Emit a document footer. Must be paired with a prior call to start().
	 */
	final public function drain() {
		$this->state( 'drain' );

		$this->flattenBuffer();

		$rdf = join( '', $this->buffer );
		$this->buffer = array();

		return $rdf;
	}

	/**
	 * @see RdfWriter::reset
	 *
	 * @note Does not reset the blank node counter, because it may be shared.
	 */
	public function reset() {
		$this->buffer = array();
		$this->state = 'start'; //TODO: may depend on role
	}

	/**
	 * Calls drain() an any RdfWriter instances in $this->buffers, and replaces them
	 * in $this->buffer with the string returned by the drain() call.
	 */
	private function flattenBuffer() {
		foreach ( $this->buffer as &$b ) {
			if ( $b instanceof \Closure ) {
				$b = $b();
			}
			if ( $b instanceof RdfWriter ) {
				$b = $b->drain();
			}
		}
	}

	final public function prefix( $prefix, $uri ) {
		$this->state( 'document' );

		$this->writePrefix( $prefix, $uri );
	}

	final public function about( $subject ) {
		//FIXME: skip if same as previous (and state ok)!
		$this->state( 'subject' );

		$this->writeSubject( $subject );
		return $this;
	}

	final public function a( $type ) {
		return $this->say( 'a' )->is( $type );
	}

	final public function hasr( $verb, $resource ) {
		return $this->say( $verb )->is( $resource );
	}

	final public function hast( $verb, $text, $lang = null ) {
		return $this->say( $verb )->text( $text, $lang );
	}

	final public function hasv( $verb, $value, $type = null ) {
		return $this->say( $verb )->value( $value, $type );
	}

	final public function say( $verb ) {
		//FIXME: skip if same as previous (and state ok)!
		$this->state( 'predicate' );

		$this->writePredicate( $verb );
		return $this;
	}

	final public function is( $object ) {
		$this->state( 'object' );

		$this->writeResource( $object );
		return $this;
	}

	final public function text( $text, $language = null ) {
		$this->state( 'object' );

		$this->writeText( $text, $language );
		return $this;
	}

	final public function value( $value, $type = null ) {
		$this->state( 'object' );

		if ( $type === null && !is_string( $value ) ) {
			$vtype = gettype( $value );
			switch ( $vtype ) {
				case 'integer':
					$type = 'xsd:integer';
					$value = "$value";
					break;

				case 'double':
					$type = 'xsd:double';
					$value = "$value";
					break;

				case 'boolean':
					$type = 'xsd:boolean';
					$value = $value ? 'true' : 'false';
					break;
			}
		}

		$this->writeValue( $value, $type );
		return $this;
	}

	final protected function state( $newState ) {
		switch ( $newState ) {
			case 'document':
				$this->transitionDocument();
				break;

			case 'subject':
				$this->transitionSubject();
				break;

			case 'predicate':
				$this->transitionPredicate();
				break;

			case 'object':
				$this->transitionObject();
				break;

			case 'drain':
				$this->transitionDrain();
				break;

			default:
				throw new \InvalidArgumentException( 'invalid $newState: ' . $newState );
		}

		$this->state = $newState;
	}

	private function transitionDocument() {
		switch ( $this->state ) {
			case 'document':
				break;

			case 'start':
				$this->beginDocument();
				break;

			case 'object': // when injecting a sub-document
				$this->finishObject( 'last' );
				$this->finishPredicate( 'last' );
				$this->finishSubject();
				break;

			default:
				throw new LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'document'  );
		}
	}

	private function transitionSubject() {
		switch ( $this->state ) {
			case 'document':
				$this->beginSubject();
				break;

			case 'object':
				if ( $this->role !== self::DOCUMENT_ROLE ) {
					throw new LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'subject' );
				}

				$this->finishObject( 'last' );
				$this->finishPredicate( 'last' );
				$this->finishSubject();
				$this->beginSubject();
				break;

			default:
				throw new LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'subject' );
		}
	}

	private function transitionPredicate() {
		switch ( $this->state ) {
			case 'subject':
				$this->beginPredicate( 'first' );
				break;

			case 'object':
				if ( $this->role === self::STATEMENT_ROLE ) {
					throw new LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'subject' );
				}

				$this->finishObject( 'last' );
				$this->finishPredicate();
				$this->beginPredicate();
				break;

			default:
				throw new LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'predicate' );

		}
	}

	private function transitionObject() {
		switch ( $this->state ) {
			case 'predicate':
				$this->beginObject( 'first' );
				break;

			case 'object':
				$this->finishObject();
				$this->beginObject();
				break;

			default:
				throw new LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'object' );

		}
	}

	private function transitionDrain() {
		switch ( $this->state ) {
			case 'start':
				break;

			case 'document':
				$this->finishDocument();
				break;

			case 'object':

				$this->finishObject( 'last' );
				$this->finishPredicate( 'last' );
				$this->finishSubject();
				$this->finishDocument();
				break;

			default:
				throw new LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'object' );

		}
	}

	protected abstract function writePrefix( $prefix, $uri );

	protected abstract function writeSubject( $subject );

	protected abstract function writePredicate( $verb );

	protected abstract function writeResource( $object );

	protected abstract function writeText( $text, $language );

	protected abstract function writeValue( $literal, $type );

	protected function finishSubject() {
	}

	protected function beginSubject( $first = false ) {
	}

	protected function finishObject( $last = false ) {
	}

	protected function finishPredicate( $last = false ) {
	}

	protected function beginPredicate( $first = false ) {
	}

	protected function beginObject( $first = false ) {
	}

	protected function beginDocument() {
	}

	protected function finishDocument() {
	}

}

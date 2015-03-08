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

	private $shorthands = array();

	private $prefixes = array();

	protected $currentSubject = array( null, null );

	protected $currentPredicate = array( null, null );

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

		$this->registerShorthand( 'a', 'rdf', 'type' );

		$this->registerPrefix( 'rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' );
		$this->registerPrefix( 'xsd', 'http://www.w3.org/2001/XMLSchema#' );
	}

	/**
	 * @param string $role
	 * @param BNodeLabeler $labeler
	 *
	 * @return RdfWriterBase
	 */
	abstract protected function newSubWriter( $role, BNodeLabeler $labeler );

	protected function registerShorthand( $shorthand, $prefix, $local ) {
		$this->shorthands[$shorthand] = array( $prefix, $local );
	}

	protected function registerPrefix( $prefix, $iri ) {
		$this->prefixes[$prefix] = $iri;
	}

	protected function isShorthand( $shorthand ) {
		return isset( $this->shorthands[$shorthand] );
	}

	protected function isPrefix( $prefix ) {
		return isset( $this->prefixes[$prefix] );
	}

	protected function getPrefixes() {
		return $this->prefixes;
	}

	/**
	 * @return RdfWriter
	 */
	final public function sub() {
		//FIXME: don't mess with the state, enqueue the writer to be placed in the buffer
		// later, on the next transtion to subject|document|drain
		$this->state( 'document' );

		$writer = $this->newSubWriter( self::DOCUMENT_ROLE, $this->labeler );
		$writer->state = 'document';

		// share registered prefixes
		$writer->prefixes &= $this->prefixes;

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
		$numArgs = func_num_args();

		for ( $i = 0; $i < $numArgs; $i++ ) {
			$s = func_get_arg( $i );
			$this->buffer[] = $s;
		}
	}

	protected function expandShorthand( &$base, &$local ) {
		if ( $local === null && isset( $this->shorthands[$base] ) ) {
			list( $base, $local ) = $this->shorthands[$base];
		}
	}

	protected function expandQName( &$base, &$local ) {
		if ( $local !== null && $base !== '_' ) {
			if ( isset( $this->prefixes[$base] ) ) {
				$base = $this->prefixes[$base] . $local; //XXX: can we avoid this concat?
				$local = null;
			} else {
				throw new LogicException( 'Unknown prefix: ' . $base );
			}
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

		$this->currentSubject = array( null, null );
		$this->currentPredicate = array( null, null );

		$this->prefixes = array();
		$this->registerPrefix( 'rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' );
		$this->registerPrefix( 'xsd', 'http://www.w3.org/2001/XMLSchema#' );
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

		$this->registerPrefix( $prefix, $uri );
		$this->writePrefix( $prefix, $uri );
	}

	final public function about( $base, $local = null ) {
		$this->expandSubject( $base, $local );

		if ( $base === $this->currentSubject[0] && $local === $this->currentSubject[1] ) {
			return $this; // redundant about() call
		}

		$this->state( 'subject' );

		$this->currentSubject[0] = $base;
		$this->currentSubject[1] = $local;
		$this->currentPredicate[0] = null;
		$this->currentPredicate[1] = null;

		$this->writeSubject( $base, $local );
		return $this;
	}

	final public function a( $base, $local = null ) {
		return $this->say( 'a' )->is( $base, $local );
	}

	final public function say( $base, $local = null ) {
		$this->expandPredicate( $base, $local );

		if ( $base === $this->currentPredicate[0] && $local === $this->currentPredicate[1] ) {
			return $this; // redundant about() call
		}

		$this->state( 'predicate' );

		$this->currentPredicate[0] = $base;
		$this->currentPredicate[1] = $local;

		$this->writePredicate( $base, $local );
		return $this;
	}

	final public function is( $base, $local = null ) {
		$this->state( 'object' );

		$this->expandResource( $base, $local );
		$this->writeResource( $base, $local );
		return $this;
	}

	final public function text( $text, $language = null ) {
		$this->state( 'object' );

		$this->writeText( $text, $language );
		return $this;
	}

	final public function value( $value, $typeBase = null, $typeLocal = null ) {
		$this->state( 'object' );

		if ( $typeBase === null && !is_string( $value ) ) {
			$vtype = gettype( $value );
			switch ( $vtype ) {
				case 'integer':
					$typeBase = 'xsd';
					$typeLocal = 'integer';
					$value = "$value";
					break;

				case 'double':
					$typeBase = 'xsd';
					$typeLocal = 'double';
					$value = "$value";
					break;

				case 'boolean':
					$typeBase = 'xsd';
					$typeLocal = 'boolean';
					$value = $value ? 'true' : 'false';
					break;
			}
		}

		$this->expandType( $typeBase, $typeLocal );

		$this->writeValue( $value, $typeBase, $typeLocal );
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

	protected abstract function writeSubject( $base, $local = null );

	protected abstract function writePredicate( $base, $local = null );

	protected abstract function writeResource( $base, $local = null );

	protected abstract function writeText( $text, $language );

	protected abstract function writeValue( $literal, $typeBase, $typeLocal = null );

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

	protected function expandSubject( &$base, &$local ) {
	}

	protected function expandPredicate( &$base, &$local ) {
	}

	protected function expandResource( &$base, &$local ) {
	}

	protected function expandType( &$base, &$local ) {
	}

}

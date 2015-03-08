<?php

namespace Wikibase\RDF;
use InvalidArgumentException;
use LogicException;

/**
 * Base class for RdfEmitter implementations
 *
 * @todo: FIXME: share code with NTriplesRdfEmitter!
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
abstract class RdfEmitterBase implements RdfEmitter {

	/**
	 * @var array An array of strings or RdfEmitters.
	 */
	private $buffer = array();

	/**
	 * @var string the current state
	 */
	private $state = 'document';

	private $blankNodeCounter = 0;

	const DOCUMENT_ROLE = 'document';
	const BNODE_ROLE = 'bnode';
	const STATEMENT_ROLE = 'statement';

	/**
	 * @var string
	 */
	private $role;

	function __construct( $role ) {
		$this->role = $role;
	}

	/**
	 * @return string a string corresponding to one of the the XXX_ROLE constants.
	 */
	final public function getRole() {
		return $this->role;
	}

	final protected function emit() {
		$args = func_get_args();

		foreach ( $args as $s ) {
			$this->buffer[] = $s;
		}
	}

	/**
	 * @param string|null $label node label, will be generated if not given.
	 *
	 * @return mixed A URI container (may just be a string)
	 */
	final public function blank( $label = null ) {
		$this->blankNodeCounter ++;

		if ( $label === null ) {
			$label = 'n' . $this->blankNodeCounter;
		}

		return '_:' . $label;
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
		$this->state( 'document' );

		$this->flattenBuffer();

		$rdf = join( '', $this->buffer );
		$this->buffer = array();

		return $rdf;
	}

	/**
	 * Calls drain() an any RdfEmitter instances in $this->buffers, and replaces them
	 * in $this->buffer with the string returned by the drain() call.
	 */
	private function flattenBuffer() {
		foreach ( $this->buffer as &$b ) {
			if ( $b instanceof RdfEmitter ) {
				$b = $b->drain();
			}
		}
	}

	final public function prefix( $prefix, $uri ) {
		if ( $this->state !== 'document' ) {
			throw new LogicException( 'Bad transition: prefixes must be declared before emitting statements.' );
		}

		$this->emitPrefix( $prefix, $uri );
	}

	final public function about( $subject ) {
		$this->state( 'subject' );

		$this->emitAbout( $subject );
		return $this;
	}

	final public function predicate( $verb ) {
		$this->state( 'predicate' );

		$this->emitPredicate( $verb );
		return $this;
	}

	final public function resource( $object ) {
		$this->state( 'object' );

		$this->emitResource( $object );
		return $this;
	}

	final public function text( $text, $language = null ) {
		$this->state( 'object' );

		$this->emitText( $text, $language );
		return $this;
	}

	final public function value( $literal, $type = null ) {
		$this->state( 'object' );

		$this->emitValue( $literal, $type );
		return $this;
	}

	private function state( $newState ) {
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

			default:
				throw new \InvalidArgumentException( 'invalid $newState: ' . $newState );
		}

		$this->state = $newState;
	}

	private function transitionDocument() {
		switch ( $this->state ) {
			case 'subject':
				throw new LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'document' );

			case 'predicate':
				throw new LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'document'  );

			case 'object':
				$this->finishAbout();
				break;
		}
	}

	private function transitionSubject() {
		switch ( $this->state ) {
			case 'document':
				$this->beginAbout();
				break;

			case 'subject':
				throw new LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'subject' );

			case 'predicate':
				throw new LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'subject' );

			case 'object':
				if ( $this->role !== self::DOCUMENT_ROLE ) {
					throw new LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'subject' );
				}

				$this->finishObject( 'last' );
				$this->finishPredicate( 'last' );
				$this->finishAbout();
				break;
		}
	}

	private function transitionPredicate() {
		switch ( $this->state ) {
			case 'document':
				throw new LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'predicate' );

			case 'subject':
				$this->beginPredicate( 'first' );
				break;

			case 'predicate':
				throw new LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'predicate' );

			case 'object':
				if ( $this->role === self::STATEMENT_ROLE ) {
					throw new LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'subject' );
				}

				$this->finishObject( 'last' );
				$this->finishPredicate();
				$this->beginPredicate();
				break;
		}
	}

	private function transitionObject() {
		switch ( $this->state ) {
			case 'document':
				throw new LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'object' );

			case 'subject':
				throw new LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'object' );

			case 'predicate':
				$this->beginObject( 'first' );
				break;

			case 'object':
				$this->finishObject();
				$this->beginObject();
				break;
		}
	}

	protected abstract function emitPrefix( $prefix, $uri );

	protected abstract function emitAbout( $subject );

	protected abstract function emitPredicate( $verb );

	protected abstract function emitResource( $object );

	protected abstract function emitText( $text, $language );

	protected abstract function emitValue( $literal, $type );

	protected function finishAbout() {
	}

	protected function beginAbout() {
	}

	protected function finishObject( $last = false ) {
	}

	protected function finishPredicate( $last = false ) {
	}

	protected function beginPredicate( $first = false ) {
	}

	protected function beginObject( $first = false ) {
	}

}

/*
$emitter = new TurtleRdfEmitter();

$emitter->start();

$emitter->prefix( 'foo', 'http://acme.com/foo#' );
$emitter->prefix( 'wdq', 'http://www.wikidata.org/item/' );
$emitter->prefix( 'wdp', 'http://www.wikidata.org/property/' );

$emitter->about( 'wdq:Q23' )
	->predicate( 'https://something/' )
		->text( 'Hello' )
		->text( 'Hallo', 'de' )
		->value( '25', 'xsd:Number' )
	->predicate( 'wdp:P17' )
		->resource( 'wdq:Q63' )
		->resource( 'https://com.test' )
		->resource( $blank = $emitter->blank() )
;

$emitter->predicate( 'wdp:P117' )
	->resource( $blank );

$emitter->about( $blank )
	->predicate( 'https://something/' )
	->text( 'Hello' )
;

$emitter->finish();
*/
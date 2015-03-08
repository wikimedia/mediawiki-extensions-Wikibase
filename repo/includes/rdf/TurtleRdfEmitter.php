<?php

namespace Wikibase\RDF;

/**
 * TurtleRdfEmitter
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class TurtleRdfEmitter implements RdfEmitter {

	/**
	 * @var string the current state
	 */
	private $state = 'document';

	private $blankNodeCounter = 0;

	private $badChars = array(
		"\"",
		"\\",
		"\0",
		"\n",
		"\r",
		"\t",
	);

	private $badCharEscapes = array(
		'\"',
		'\\\\',
		'\0',
		'\n',
		'\r',
		'\t',
	);

	private function emit() {
		//FIXME inject output stream / file handle
		//FIXME: inject indent level for sub-emitters (for use with inlined blank nodes)
		$args = func_get_args();

		foreach ( $args as $s ) {
			print $s;
		}
	}

	public function uri( $uriString ) {
		return '<' . $uriString . '>'; //FIXME escape
	}

	/**
	 * @param string|null $label node label, will be generated if not given.
	 *
	 * @return mixed A URI container (may just be a string)
	 */
	public function blank( $label = null ) {
		$this->blankNodeCounter ++;

		if ( $label === null ) {
			$label = 'n' . $this->blankNodeCounter;
		}

		return '_:' . $label;
	}

	/**
	 * @param string $prefix
	 * @param string $name
	 *
	 * @return mixed An reference container (implementation specific, may be an object or a string)
	 */
	public function qname( $prefix, $name ) {
		return $prefix . ':' . $name;
	}

	/**
	 * Emit a document header. Must be paired with a later call to finish().
	 */
	public function start() {
		$this->state( 'document' );
	}

	/**
	 * Emit a document footer. Must be paired with a prior call to start().
	 */
	public function finish() {
		$this->state( 'document' );
	}

	public function prefix( $prefix, $uri ) {
		$this->emit( '@prefix ' . $prefix . ' : ' . $this->uri( $uri ), "\n" );
	}

	public function about( $subject ) {
		$this->state( 'subject' );

		$this->emit( $subject );
		return $this;
	}

	public function predicate( $verb ) {
		$this->state( 'predicate' );

		$this->emit( $verb );
		return $this;
	}

	public function resource( $object ) {
		$this->state( 'object' );

		$this->emit( $object );
		return $this;
	}

	public function text( $text, $language = null ) {
		$this->state( 'object' );

		$quoted = str_replace( $this->badChars, $this->badCharEscapes, $text );
		$this->emit( '"', $quoted, '"' );

		if ( $language !==null ) {
			$this->emit( '@', $language );
		}
		return $this;
	}

	public function value( $literal, $type = null ) {
		$this->state( 'object' );

		$quoted = str_replace( $this->badChars, $this->badCharEscapes, $literal );
		$this->emit( '"', $quoted, '"' );

		if ( $type !==null ) {
			$this->emit( '^^', $type );
		}
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
				throw new \LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'document'  );
				break;

			case 'predicate':
				throw new \LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'document'  );
				break;

			case 'object':
				$this->emit( ' .', "\n" );
				break;
		}
	}

	private function transitionSubject() {
		switch ( $this->state ) {
			case 'document':
				$this->emit( "\n" );
				break;

			case 'subject':
				throw new \LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'subject' );
				break;

			case 'predicate':
				throw new \LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'subject' );
				break;

			case 'object':
				$this->emit( ' .', "\n\n" );
				break;
		}
	}

	private function transitionPredicate() {
		switch ( $this->state ) {
			case 'document':
				throw new \LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'predicate' );
				break;

			case 'subject':
				$this->emit( ' ' );
				break;

			case 'predicate':
				throw new \LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'predicate' );
				break;

			case 'object':
				$this->emit( ' ;', "\n\t" );
				break;
		}
	}

	private function transitionObject() {
		switch ( $this->state ) {
			case 'document':
				throw new \LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'object' );
				break;

			case 'subject':
				throw new \LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'object' );
				break;

			case 'predicate':
				$this->emit( ' ' );
				break;

			case 'object':
				$this->emit( ',', "\n\t\t" );
				break;
		}
	}

}

/*
$emitter = new TurtleRdfEmitter();

$emitter->start();

$emitter->prefix( 'foo', 'http://acme.com/foo#' );
$emitter->prefix( 'wdq', 'http://www.wikidata.org/item/' );
$emitter->prefix( 'wdp', 'http://www.wikidata.org/property/' );

$emitter->about( $emitter->qname( 'wdq', 'Q23' ) )
	->predicate( $emitter->uri( 'https://something/' ) )
		->text( 'Hello' )
		->text( 'Hallo', 'de' )
		->value( '25', $emitter->qname( 'xsd', 'Number' ) )
	->predicate( $emitter->qname( 'wdp', 'P17' ) )
		->resource( $emitter->qname( 'wdq', 'Q63' ) )
		->resource( $emitter->uri( 'https://com.test' ) )
		->resource( $blank = $emitter->blank() )
;

$emitter->predicate( $emitter->qname( 'wdp', 'P117' ) )
	->resource( $blank );

$emitter->about( $blank )
	->predicate( $emitter->uri( 'https://something/' ) )
	->text( 'Hello' )
;

$emitter->finish();
*/
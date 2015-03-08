<?php

namespace Wikibase\RDF;

/**
 * XmlRdfEmitter
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class XmlRdfEmitter implements RdfEmitter {

	/**
	 * @var string the current state
	 */
	private $state = 'root';

	private $currentPredicate  = null;

	private $blankNodeCounter = 0;

	private $badChars = array(
		'"',
		'&',
		'<',
		'>',
	);

	private $badCharEscapes = array(
		'&quot;',
		'&amp;',
		'&lt;',
		'&gt;',
	);

	private function emit() {
		//FIXME inject output stream / file handle
		//FIXME: inject indent level for sub-emitters (for use with inlined blank nodes)
		$args = func_get_args();

		foreach ( $args as $s ) {
			print $s;
		}
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

	private function escape( $text ) {
		return str_replace( $this->badChars, $this->badCharEscapes, $text );
	}

	private function checkName( $name ) {
		if ( !preg_match( '!^\w+(:\w+)?$!', $name ) ) {
			throw new \InvalidArgumentException( 'Invalid name: ' . $name );
		}
	}

	private function tag( $name, $attributes = array(), $close = '' ) {
		$this->checkName( $name );

		$this->emit( '<', $name );

		foreach ( $attributes as $attr => $value ) {
			$this->checkName( $attr );
			$this->emit( ' ', $attr, '=', '"', $this->escape( $value ), '"' );
		}

		$this->emit( $close, '>' );
	}

	private function close( $name ) {
		$this->emit( '</', $name, '>' );
	}

	/**
	 * Emit a document header. Must be paired with a later call to finish().
	 */
	public function start() {
		$this->state( 'root' );

		$this->emit( '<?xml version="1.0"?>', "\n" );
		$this->tag( 'rdf:RDF', array(
			'xmlns:rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
		) );
	}

	/**
	 * Emit a document footer. Must be paired with a prior call to start().
	 */
	public function finish() {
		$this->state( 'root' );
		$this->close( 'rdf:RDF' );
	}

	public function prefix( $prefix, $uri ) {
		//FIXME: collect prefixes, emit root tag only after all prefixes are declared and we enter the "subject" state!
	}

	public function about( $subject ) {
		$this->state( 'subject' );

		$this->tag( 'rdf:Description', array(
			'rdf:about' => $subject
		) );

		return $this;
	}

	public function predicate( $verb ) {
		$this->state( 'predicate' );

		if ( $verb === 'a' ) {
			$verb = 'rdf:type';
		}

		//FIXME: check that $verb is a QName
		$this->currentPredicate = $verb;

		return $this;
	}

	public function resource( $object ) {
		$this->state( 'object' );

		$this->tag( $this->currentPredicate, array(
			'rdf:resource' => $object
		), '/' );

		return $this;
	}

	public function text( $text, $language = null ) {
		$this->state( 'object' );

		$attr = empty( $language ) ? array() : array( 'lang' => $language );

		$this->tag( $this->currentPredicate, $attr );
		$this->emit( $this->escape( $text ) );
		$this->close( $this->currentPredicate );

		return $this;
	}

	public function value( $literal, $type = null ) {
		$this->state( 'object' );

		$attr = empty( $type ) ? array() : array( 'rdf:datatype' => $type );

		$this->tag( $this->currentPredicate, $attr );
		$this->emit( $this->escape( $literal ) );
		$this->close( $this->currentPredicate );

		return $this;
	}

	private function state( $newState ) {
		switch ( $newState ) {
			case 'root':
				$this->transitionRoot();
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

	private function transitionRoot() {
		switch ( $this->state ) {
			case 'subject':
				throw new \LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'root'  );
				break;

			case 'predicate':
				throw new \LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'root'  );
				break;

			case 'object':
				$this->emit( "\n\t" );
				$this->close( 'rdf:Description' );
				$this->emit( "\n" );
				break;
		}
	}

	private function transitionSubject() {
		switch ( $this->state ) {
			case 'root':
				$this->emit( "\n\n\t" );
				break;

			case 'subject':
				throw new \LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'subject' );
				break;

			case 'predicate':
				throw new \LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'subject' );
				break;

			case 'object':
				$this->emit( "\n\t" );
				$this->close( 'rdf:Description' );
				$this->emit( "\n\n\t" );
				break;
		}
	}

	private function transitionPredicate() {
		switch ( $this->state ) {
			case 'root':
				throw new \LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'predicate' );
				break;

			case 'subject':
				break;

			case 'predicate':
				throw new \LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'predicate' );
				break;

			case 'object':
				break;
		}
	}

	private function transitionObject() {
		switch ( $this->state ) {
			case 'root':
				throw new \LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'object' );
				break;

			case 'subject':
				throw new \LogicException( 'Bad transition: ' . $this->state. ' -> ' . 'object' );
				break;

			case 'predicate':
				$this->emit( "\n\t\t" );
				break;

			case 'object':
				$this->emit( "\n\t\t" );
				break;
		}
	}

}

/*
$emitter = new XmlRdfEmitter();

$emitter->start();

$emitter->prefix( 'foo', 'http://acme.com/foo#' );
$emitter->prefix( 'wdq', 'http://www.wikidata.org/item/' );
$emitter->prefix( 'wdp', 'http://www.wikidata.org/property/' );

$emitter->about( 'wdq:Q23' )
	->predicate( 'something:x' )
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
	->predicate( 'something:x' )
	->text( 'Hello' )
;

$emitter->finish();
*/
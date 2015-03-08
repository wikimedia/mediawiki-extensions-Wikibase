<?php

namespace Wikibase\RDF;
use InvalidArgumentException;

/**
 * NTriplesRdfEmitter
 *
 * @todo: FIXME: share code with TurtleRdfEmitter!
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class NTriplesRdfEmitter /* implements RdfEmitter */ {

	private $prefixes = array();

	private $currentSubject = null;

	private $currentPredicate = null;

	private $blankNodeCounter = 0;

	private $shorthands = '';

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

	private $badUriChars = array(
		"%",
		"<",
		">",
		" ",
		"\n",
		"\r",
		"\t",
	);

	private $badUriCharEscapes = array(
		'%25',
		'%3C',
		'%3E',
		'%20',
		'%0D',
		'%0A',
		'%09',
	);

	private function emit() {
		//FIXME inject output stream / file handle
		//FIXME: inject indent level for sub-emitters (for use with inlined blank nodes)
		$args = func_get_args();

		foreach ( $args as $s ) {
			print $s;
		}
	}

	private function quoteResource( $s ) {
		$other = func_get_args();
		array_shift( $other );

		if ( in_array( $s, $other ) && array_key_exists( $s, $this->shorthands ) ) {
			$s = $this->shorthands[$s];
		}

		if ( preg_match( '!^(\w+):(\w+)$!', $s, $m ) ) {
			$ns = $m[1];
			if ( array_key_exists( $ns, $this->prefixes ) ) {
				$s = $this->prefixes[$ns] . $s;
			}
		}

		if ( preg_match( '!^_:\w+!', $s ) ) {
			return $s; // named (blank) node
		} elseif ( preg_match( '!^\w+://\w+!', $s ) ) {
			return $this->quoteURI( $s );
		} else {
			throw new InvalidArgumentException( 'Not a valid resource reference: ' . $s );
		}
	}

	private function quoteURI( $uri ) {
		//FIXME: more robust escaping
		$quoted = str_replace( $this->badUriChars, $this->badUriCharEscapes, $uri );

		return '<' . $quoted . '>';
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
		$this->prefixes[$prefix] = $uri;
	}

	public function about( $subject ) {
		$this->state( 'subject' );

		$subject = $this->quoteResource( $subject );
		$this->currentSubject = $subject;
		return $this;
	}

	public function predicate( $verb ) {
		$this->state( 'predicate' );

		$verb = $this->quoteResource( $verb, 'a' );
		$this->currentPredicate = $verb;

		return $this;
	}

	private function triple( $object ) {
		$this->emit( $this->currentSubject, ' ' );
		$this->emit( $this->currentPredicate, ' ' );
		$this->emit( $object, ' ' );
		$this->emit( "\n" );
	}

	public function resource( $object ) {
		$this->state( 'object' );

		$object = $this->quoteResource( $object );
		$this->triple( $object );
		return $this;
	}

	private function quoteLiteral( $s ) {
		//FIXME: more robust escaping
		return '"' . str_replace( $this->badChars, $this->badCharEscapes, $s ) . '"';
	}

	public function text( $text, $language = null ) {
		$this->state( 'object' );

		$quoted = $this->quoteLiteral( $text );

		if ( $language !==null ) {
			$quoted .= '@' . $language;
		}

		$this->triple( $quoted );
		return $this;
	}

	public function value( $literal, $type = null ) {
		$this->state( 'object' );

		$quoted = $this->quoteLiteral( $literal );

		if ( $type !==null ) {
			$type = $this->quoteResource( $type );
			$quoted .= '^^' . $type;
		}

		$this->triple( $quoted );
		return $this;
	}

	private function state( $newState ) {
		// state transitions are not relevant
	}

}

/*
$emitter = new NTriplesRdfEmitter();

$emitter->start();

$emitter->prefix( 'foo', 'http://acme.com/foo#' );
$emitter->prefix( 'wdq', 'http://www.wikidata.org/item/' );
$emitter->prefix( 'wdp', 'http://www.wikidata.org/property/' );
$emitter->prefix( 'xsd', 'http://www.w3.org/2001/XMLSchema#' );

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
<?php

namespace Wikibase\RDF;
use InvalidArgumentException;

/**
 * NTriplesRdfEmitter
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class NTriplesRdfEmitter extends TurtleRdfEmitter {

	private $prefixes = array();

	private $currentSubject = null;

	private $currentPredicate = null;

	private $shorthands = array(
		'a' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type'
	);

	protected function quoteResource( $s ) {
		if ( $s instanceof RdfEmitter ) {
			return $s;
		}

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

	protected function emitPrefix( $prefix, $uri ) {
		$this->prefixes[$prefix] = $uri;
	}

	protected function emitAbout( $subject ) {
		$subject = $this->quoteResource( $subject );
		$this->currentSubject = $subject;
	}

	protected function emitPredicate( $verb ) {
		$verb = $this->quoteResource( $verb, 'a' );
		$this->currentPredicate = $verb;
	}

	private function emitTriple( $object ) {
		!!! this gets the state transition out of sync with the buffer. ugh.
		$this->emit( $this->currentSubject, ' ' );
		$this->emit( $this->currentPredicate, ' ' );
		$this->emit( $object, ' ' );
		$this->emit( ".\n" );
	}

	protected function emitResource( $object ) {
		$object = $this->quoteResource( $object );
		$this->emitTriple( $object );
	}

	protected function emitText( $text, $language = null ) {
		$quoted = $this->quoteLiteral( $text );

		if ( $language !==null ) {
			$quoted .= '@' . $language;
		}

		$this->emitTriple( $quoted );
	}

	protected function emitValue( $literal, $type = null ) {
		$quoted = $this->quoteLiteral( $literal );

		if ( $type !==null ) {
			$type = $this->quoteResource( $type );
			$quoted .= '^^' . $type;
		}

		$this->emitTriple( $quoted );
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
<?php

namespace Wikibase\RDF;
use InvalidArgumentException;

/**
 * TurtleRdfEmitter
 *
 * @todo: FIXME: share code with NTriplesRdfEmitter!
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class TurtleRdfEmitter extends RdfEmitterBase {

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

	protected function quoteResource( $s ) {
		if ( $s instanceof RdfEmitter ) {
			return $s;
		}

		$other = func_get_args();
		array_shift( $other );

		if ( preg_match( '!^\w+:\w+$!', $s ) || in_array( $s, $other ) ) {
			return $s;
		} elseif ( preg_match( '!^\w+://\w+!', $s ) ) {
			return $this->quoteURI( $s );
		} else {
			throw new InvalidArgumentException( 'Not a valid resource reference: ' . $s );
		}
	}

	protected function quoteURI( $uri ) {
		//FIXME: more robust escaping
		$quoted = str_replace( $this->badUriChars, $this->badUriCharEscapes, $uri );

		return '<' . $quoted . '>';
	}

	protected function emitPrefix( $prefix, $uri ) {
		$this->emit( '@prefix ' . $prefix . ' : ' . $this->quoteURI( $uri ), "\n" );
	}

	protected function emitAbout( $subject ) {
		$subject = $this->quoteResource( $subject );
		$this->emit( $subject );
	}

	protected function emitPredicate( $verb ) {
		$verb = $this->quoteResource( $verb, 'a' );
		$this->emit( $verb );
	}

	protected function emitResource( $object ) {
		$object = $this->quoteResource( $object );
		$this->emit( $object );
	}

	protected function quoteLiteral( $s ) {
		//FIXME: more robust escaping
		return '"' . str_replace( $this->badChars, $this->badCharEscapes, $s ) . '"';
	}

	protected function emitText( $text, $language = null ) {
		//FIXME: more robust escaping
		$quoted = $this->quoteLiteral( $text );
		$this->emit( '"', $quoted, '"' );

		if ( $language !==null ) {
			$this->emit( '@', $language );
		}
	}

	protected function emitValue( $literal, $type = null ) {
		//FIXME: more robust escaping
		$quoted = $this->quoteLiteral( $literal );
		$this->emit( '"', $quoted, '"' );

		if ( $type !==null ) {
			$type = $this->quoteResource( $type );
			$this->emit( '^^', $type );
		}
	}

	protected function beginAbout() {
		$this->emit( "\n" );
	}

	protected function finishAbout() {
		$this->emit( ' .', "\n" );
	}

	protected function beginPredicate( $first = false ) {
		if ( $first ) {
			$this->emit( ' ' );
		}
	}

	protected function finishPredicate( $last = false ) {
		if ( !$last ) {
			$this->emit( ' ;', "\n\t" );
		}
	}

	protected function beginObject( $first = false ) {
		// noop
	}

	protected function finishObject( $last = false ) {
		if ( !$last ) {
			$this->emit( ',', "\n\t\t" );
		}
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
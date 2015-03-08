<?php

namespace Wikibase\RDF;

use InvalidArgumentException;

/**
 * Helper class for quoting literals and URIs in N3 output.
 * Optionally supports shorthand and prefix resolution.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class N3Quoter {

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
		"<",
		">",
		"\"",
		" ",
		"\n",
		"\r",
		"\t",
	);

	private $badUriCharEscapes = array(
		'%3C',
		'%3E',
		'%22',
		'%20',
		'%0D',
		'%0A',
		'%09',
	);


	private $shorthands = array();

	private $prefixes = array();

	private $allowQNames = true;

	/**
	 * @var UnicodeEscaper
	 */
	private $escaper = null;

	/**
	 * @return boolean
	 */
	public function getAllowQNames() {
		return $this->allowQNames;
	}

	/**
	 * @param boolean $allowQNames
	 */
	public function setAllowQNames( $allowQNames ) {
		$this->allowQNames = $allowQNames;
	}

	/**
	 * @param $escapeUnicode
	 */
	public function setEscapeUnicode( $escapeUnicode ) {
		$this->escaper = $escapeUnicode ? new UnicodeEscaper() : null;
	}

	public function registerShorthand( $name, $uri ) {
		if ( !is_string( $name ) ) {
			throw new InvalidArgumentException( '$name must be a string' );
		}

		if ( !is_string( $uri ) ) {
			throw new InvalidArgumentException( '$uri must be a string' );
		}

		$this->shorthands[$name] = $uri;
	}

	public function registerPrefix( $prefix, $base ) {
		if ( !is_string( $prefix ) ) {
			throw new InvalidArgumentException( '$prefix must be a string' );
		}

		if ( !is_string( $base ) ) {
			throw new InvalidArgumentException( '$base must be a string' );
		}

		$this->prefixes[$prefix] = $base;
	}

	public function quoteResource( $s ) {
		$other = func_get_args();
		array_shift( $other );

		if ( in_array( $s, $other ) ) {
			// allowed but unregistered shorthands are passed through unchanged
			if ( array_key_exists( $s, $this->shorthands ) ) {
				$s = $this->shorthands[$s];
			}
		}

		//FIXME: use explode
		if ( preg_match( '!^(\w+):([-.:+\w]+)$!', $s, $m ) ) { //FIXME: regex for QNames
			$ns = $m[1];
			if ( $ns === '_' ) {
				return $s; // blank node
			} elseif ( array_key_exists( $ns, $this->prefixes ) ) {
				$s = $this->prefixes[$ns] . $m[2];
			}
		}

		if ( preg_match( '!^\w+:(//|[\w.]+@)\w+!', $s ) ) {
			return $this->quoteURI( $s );
		} else {
			if ( $this->allowQNames ) {
				return $s;
			} else {
				throw new InvalidArgumentException( 'Not a valid resource reference: ' . $s );
			}
		}
	}

	public function quoteURI( $uri ) {
		//FIXME: more robust escaping
		$quoted = str_replace( $this->badUriChars, $this->badUriCharEscapes, $uri );

		return '<' . $quoted . '>';
	}

	public function quoteText( $s ) {
		//FIXME: more robust escaping
		$escaped = str_replace( $this->badChars, $this->badCharEscapes, $s );

		if ( $this->escaper !== null ) {
			$escaped = $this->escaper->escapeString( $escaped );
		}

		return '"' . $escaped . '"';
	}

	public function getLiteral( $text, $joiner, $suffix = null ) {
		if ( $suffix !== null && !is_string( $suffix ) ) {
			throw new InvalidArgumentException( '$suffix must be a string' );
		}

		$literal = $this->quoteText( $text );

		if ( $suffix !==null && $suffix !== '' ) {
			$literal .=  $joiner . $suffix;
		}

		return $literal;
	}

}

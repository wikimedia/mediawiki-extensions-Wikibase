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

	private $badUriChars = array(
		"<",
		">",
		"\"",
		" ",
	);

	private $badUriCharEscapes = array(
		'%3C',
		'%3E',
		'%22',
		'%20',
	);

	/**
	 * @var UnicodeEscaper
	 */
	private $escaper = null;

	private $escapeIRIs = false;

	/**
	 * @param bool $escapeUnicode
	 */
	public function setEscapeUnicode( $escapeUnicode ) {
		$this->escaper = $escapeUnicode ? new UnicodeEscaper() : null;
	}

	public function escapeIRI( $iri ) {
		//FIXME: more robust escaping;
		//FIXME: apply unicode escaping?!
		$quoted = str_replace( $this->badUriChars, $this->badUriCharEscapes, $iri );
		return $quoted;
	}

	public function escapeLiteral( $s ) {
		$escaped = addcslashes( $s, "\r\n\t\0\\\"" );

		if ( $this->escaper !== null ) {
			$escaped = $this->escaper->escapeString( $escaped );
		}

		return $escaped;
	}

}

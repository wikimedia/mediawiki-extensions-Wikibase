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

	/**
	 * @var UnicodeEscaper
	 */
	private $escaper = null;

	/**
	 * @param $escapeUnicode
	 */
	public function setEscapeUnicode( $escapeUnicode ) {
		$this->escaper = $escapeUnicode ? new UnicodeEscaper() : null;
	}

	public function escapeIRI( $uri ) {
		//FIXME: more robust escaping;
		//FIXME: apply unicode escaping?!
		$quoted = str_replace( $this->badUriChars, $this->badUriCharEscapes, $uri );

		return $quoted;
	}

	public function escapeLiteral( $s ) {
		//FIXME: more robust escaping
		$escaped = str_replace( $this->badChars, $this->badCharEscapes, $s );

		if ( $this->escaper !== null ) {
			$escaped = $this->escaper->escapeString( $escaped );
		}

		return $escaped;
	}

}

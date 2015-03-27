<?php

namespace Wikimedia\Purtle;

/**
 * Helper class for quoting literals and URIs in N3 output.
 * Optionally supports shorthand and prefix resolution.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class N3Quoter {

	/**
	 * @var UnicodeEscaper
	 */
	private $escaper = null;

	/**
	 * @param bool $escapeUnicode
	 */
	public function setEscapeUnicode( $escapeUnicode ) {
		$this->escaper = $escapeUnicode ? new UnicodeEscaper() : null;
	}

	public function escapeIRI( $iri ) {
		$quoted = strtr( $iri, array(
			' ' => '%20',
			'"' => '%22',
			'<' => '%3C',
			'>' => '%3E',
		) );

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

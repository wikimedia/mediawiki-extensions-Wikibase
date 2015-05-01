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
		//FIXME: apply unicode escaping?!
		return strtr( $iri, array(
				' ' => '%20',
				'"' => '%22',
				'<' => '%3C',
				'>' => '%3E',
				'\\' => '%5C',
				"`" => '%60',
				"^" => '%5E',
				"|" => '%7C',
				"{" => '%7B',
				"}" => '%7D',
		) );
	}

	public function escapeLiteral( $s ) {
		$escaped = addcslashes( $s, "\x0..\x1F\"\\" );

		if ( $this->escaper !== null ) {
			$escaped = $this->escaper->escapeString( $escaped );
		}

		return $escaped;
	}

}

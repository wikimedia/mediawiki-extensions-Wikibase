<?php

namespace Wikibase\Lib\Formatters;

use DataValues\StringValue;
use InvalidArgumentException;
use ValueFormatters\ValueFormatter;

/**
 * Formats the StringValue from a snak as an Wikitext link.
 *
 * @license GPL-2.0-or-later
 * @author Jonas Kress
 */
class InterWikiLinkWikitextFormatter implements ValueFormatter {

	/**
	 * @var string
	 */
	private $baseUrl;

	/**
	 * @param string $baseUrl Base URL, used to build links to the geo shape storage.
	 */
	public function __construct( $baseUrl ) {
		$this->baseUrl = $baseUrl;
	}

	/**
	 * @see ValueFormatter::format
	 *
	 * Formats the given page title as an Wikitext link
	 *
	 * @param StringValue $value The page title to  be turned into a link
	 *
	 * @throws InvalidArgumentException
	 * @return string Wikitext external link
	 */
	public function format( $value ) {
		if ( !( $value instanceof StringValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a StringValue.' );
		}

		return '[' .
			wfUrlencode( $this->baseUrl . $this->encodeSpaces( $value->getValue() ) ) .
			' ' .
			wfEscapeWikiText( $value->getValue() ) .
			']';
	}

	/**
	 * @param string $pageName
	 *
	 * @return string
	 */
	private function encodeSpaces( $pageName ) {
		return str_replace( ' ', '_', $pageName );
	}

}

<?php

namespace Wikibase\Parsers;

use DataValues\MonolingualTextValue;
use RuntimeException;
use ValueParsers\StringValueParser;

/**
 * Parser for monolingual text strings.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class MonolingualTextParser extends StringValueParser {

	const FORMAT_NAME = 'monolingualtext';

	/**
	 * @see StringValueParser::stringParse
	 *
	 * @note Uses the "valuelang" option to set the language code in the MonolingualTextValue.
	 *
	 * @param string $value
	 *
	 * @throws RuntimeException
	 * @return MonolingualTextValue
	 */
	protected function stringParse( $value ) {
		$this->getOptions()->requireOption( 'valuelang' );

		$lang = $this->getOptions()->getOption( 'valuelang' );

		return new MonolingualTextValue( trim( $lang ), trim( $value ) );
	}

}

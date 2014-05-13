<?php

namespace Wikibase\Parsers;

use DataValues\MonolingualTextValue;
use ValueParsers\ParseException;
use ValueParsers\StringValueParser;

/**
 * Parser for monolingual text strings.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MonolingualTextParser extends StringValueParser {

	const FORMAT_NAME = 'monolingualtext';

	/**
	 * @see StringValueParser::stringParse
	 *
	 * @note Uses the "lang" option to set the language code in
	 * the MonolingualTextValue
	 *
	 * @since 0.5
	 *
	 * @param string $value
	 *
	 * @return MonolingualTextValue
	 * @throws ParseException
	 */
	protected function stringParse( $value ) {
		$this->getOptions()->defaultOption( 'lang', 'en' );
		$lang = $this->getOptions()->getOption( 'lang' );

		return new MonolingualTextValue( trim( $lang ), trim( $value ) );
	}

}

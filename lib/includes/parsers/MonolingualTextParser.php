<?php

namespace Wikibase\Parsers;

use DataValues\IllegalValueException;
use DataValues\MonolingualTextValue;
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
	 * @throws IllegalValueException if the "valuelang" option is missing or empty
	 * @return MonolingualTextValue
	 */
	protected function stringParse( $value ) {
		if ( !$this->getOptions()->hasOption( 'valuelang' ) ) {
			throw new IllegalValueException( 'Can not construct a MonolingualTextValue without a language code.' );
		}

		$lang = $this->getOptions()->getOption( 'valuelang' );

		return new MonolingualTextValue( trim( $lang ), trim( $value ) );
	}

}

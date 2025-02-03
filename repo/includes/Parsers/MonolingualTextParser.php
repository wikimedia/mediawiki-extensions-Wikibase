<?php

namespace Wikibase\Repo\Parsers;

use DataValues\IllegalValueException;
use DataValues\MonolingualTextValue;
use ValueParsers\ParseException;
use ValueParsers\StringValueParser;

/**
 * Parser for monolingual text strings.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class MonolingualTextParser extends StringValueParser {

	private const FORMAT_NAME = 'monolingual-text';

	/**
	 * @see StringValueParser::stringParse
	 *
	 * @note Uses the "valuelang" option to set the language code in the MonolingualTextValue.
	 *
	 * @param string $value
	 *
	 * @throws ParseException if the "valuelang" option is missing or empty
	 * @return MonolingualTextValue
	 */
	protected function stringParse( $value ) {
		if ( !$this->options->hasOption( 'valuelang' ) ) {
			throw new ParseException(
				'Cannot construct a MonolingualTextValue without a language code.',
				$value,
				self::FORMAT_NAME
			);
		}

		$lang = $this->getOption( 'valuelang' );
		if ( !is_string( $lang ) ) {
			throw new ParseException(
				'MonolingualTextValue language option must be a string.',
				$value,
				self::FORMAT_NAME
			);
		}

		try {
			return new MonolingualTextValue( trim( $lang ), trim( $value ) );
		} catch ( IllegalValueException $ex ) {
			throw new ParseException( $ex->getMessage(), $value, self::FORMAT_NAME );
		}
	}

}

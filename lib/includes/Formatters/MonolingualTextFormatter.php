<?php

namespace Wikibase\Lib\Formatters;

use DataValues\MonolingualTextValue;
use InvalidArgumentException;
use ValueFormatters\ValueFormatter;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class MonolingualTextFormatter implements ValueFormatter {

	/**
	 * @see ValueFormatter::format
	 *
	 * @param MonolingualTextValue $value
	 *
	 * @throws InvalidArgumentException
	 * @return string Text
	 */
	public function format( $value ) {
		if ( !( $value instanceof MonolingualTextValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a MonolingualTextValue.' );
		}

		return $value->getText();
	}

}

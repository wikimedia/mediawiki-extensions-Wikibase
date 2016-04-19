<?php

namespace Wikibase\Formatters;

use DataValues\MonolingualTextValue;
use InvalidArgumentException;
use ValueFormatters\ValueFormatterBase;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class MonolingualTextFormatter extends ValueFormatterBase {

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

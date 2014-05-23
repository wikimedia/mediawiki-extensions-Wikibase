<?php

namespace Wikibase\Formatters;

use DataValues\MonolingualTextValue;
use InvalidArgumentException;
use ValueFormatters\ValueFormatterBase;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MonolingualTextFormatter extends ValueFormatterBase {

	/**
	 * @see ValueFormatter::format
	 */
	public function format( $value ) {
		if ( !( $value instanceof MonolingualTextValue ) ) {
			throw new InvalidArgumentException( '$value must be a MonolingualTextValue' );
		}

		return $value->getText();
	}

}

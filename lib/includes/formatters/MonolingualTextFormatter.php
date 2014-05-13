<?php

namespace Wikibase\Formatters;

use DataValues\MonolingualTextValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatterBase;

/**
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel
 */
class MonolingualTextFormatter extends ValueFormatterBase {

	/**
	 * @param FormatterOptions $options
	 */
	public function __construct( FormatterOptions $options ) {
		$this->options = $options;
	}

	/**
	 * @see ValueFormatter::format
	 */
	public function format( $value ) {
		if ( !( $value instanceof MonolingualTextValue ) ) {
			throw new \InvalidArgumentException( '$value must be a MonolingualTextValue' );
		}

		return $value->getText();
	}

}

<?php

namespace Wikibase\Lib\Formatters;

use DataValues\MonolingualTextValue;
use InvalidArgumentException;
use ValueFormatters\ValueFormatterBase;

/**
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class MonolingualWikitextFormatter extends ValueFormatterBase {

	/**
	 * Intentional override because this formatter does not consume any options.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * @see ValueFormatter::format
	 *
	 * @param MonolingualTextValue $value
	 *
	 * @throws InvalidArgumentException
	 * @return string Wikitext
	 */
	public function format( $value ) {
		if ( !( $value instanceof MonolingualTextValue ) ) {
			throw new InvalidArgumentException( '$value must be a MonolingualTextValue' );
		}

		$text = $value->getText();
		$languageCode = $value->getLanguageCode();

		return '<span lang="' . wfEscapeWikiText( $languageCode ) . '">'
			. wfEscapeWikiText( $text ) . '</span>';
	}

}

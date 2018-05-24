<?php

namespace Wikibase\Lib\Formatters;

use DataValues\MonolingualTextValue;
use InvalidArgumentException;
use ValueFormatters\ValueFormatter;

/**
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class MonolingualWikitextFormatter implements ValueFormatter {

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

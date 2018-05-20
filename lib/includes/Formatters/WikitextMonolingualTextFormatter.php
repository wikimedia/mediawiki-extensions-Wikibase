<?php

namespace Wikibase\Lib\Formatters;

use DataValues\MonolingualTextValue;
use Html;
use InvalidArgumentException;
use ValueFormatters\ValueFormatter;

/**
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class WikitextMonolingualTextFormatter implements ValueFormatter {

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
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a MonolingualTextValue.' );
		}

		return Html::element(
			'span',
			[ 'lang' => $value->getLanguageCode() ],
			wfEscapeWikiText( $value->getText() )
		);
	}

}

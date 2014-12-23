<?php

namespace Wikibase\Formatters;

use DataValues\MonolingualTextValue;
use InvalidArgumentException;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;
use Wikibase\Utils;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MonolingualHtmlFormatter extends ValueFormatterBase {

	/**
	 * @see ValueFormatter::format
	 */
	public function format( $value ) {
		if ( !( $value instanceof MonolingualTextValue ) ) {
			throw new InvalidArgumentException( '$value must be a MonolingualTextValue' );
		}

		$userLanguage = $this->getOption( ValueFormatter::OPT_LANG );

		$text = $value->getText();
		$languageCode = $value->getLanguageCode();
		$languageName = Utils::fetchLanguageName( $languageCode, $userLanguage );

		$msg = wfMessage( 'wikibase-monolingualtext' )->params(
			wfEscapeWikiText( $text ),
			wfEscapeWikiText( $languageCode ),
			wfEscapeWikiText( $languageName ) );
		return $msg->parse();
	}

}

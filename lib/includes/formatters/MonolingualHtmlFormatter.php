<?php

namespace Wikibase\Formatters;

use DataValues\MonolingualTextValue;
use ValueFormatters\FormatterOptions;
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
			throw new \InvalidArgumentException( '$value must be a MonolingualTextValue' );
		}

		$userLang = $this->getOption( ValueFormatter::OPT_LANG );

		$text = $value->getText();
		$textLang = $value->getLanguageCode();
		$textLangName = Utils::fetchLanguageName( $textLang, $userLang );

		$msg = wfMessage( 'wikibase-monolingual-text', $text, $textLang, $textLangName );
		return $msg->parse();
	}

}

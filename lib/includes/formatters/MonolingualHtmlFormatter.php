<?php

namespace Wikibase\Formatters;

use DataValues\MonolingualTextValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;
use Wikibase\Utils;

/**
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel
 */
class MonolingualHtmlFormatter extends ValueFormatterBase {

	/**
	 * @param FormatterOptions $options
	 */
	public function __construct( FormatterOptions $options ) {
		$this->options = $options;
		$this->options->defaultOption( ValueFormatter::OPT_LANG, 'en' );
	}

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

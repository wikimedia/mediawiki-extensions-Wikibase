<?php

namespace Wikibase\Formatters;

use DataValues\MonolingualTextValue;
use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;
use Wikibase\Lib\LanguageNameLookup;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MonolingualHtmlFormatter extends ValueFormatterBase {

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @param FormatterOptions $options
	 * @param LanguageNameLookup $languageNameLookup
	 */
	public function __construct( FormatterOptions $options, LanguageNameLookup $languageNameLookup ) {
		parent::__construct( $options );
		$this->languageNameLookup = $languageNameLookup;
	}

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
		$languageName = $this->languageNameLookup->getName( $languageCode, $userLanguage );

		$msg = wfMessage( 'wikibase-monolingualtext' )->params(
			wfEscapeWikiText( $text ),
			wfEscapeWikiText( $languageCode ),
			wfEscapeWikiText( $languageName ) );
		return $msg->parse();
	}

}

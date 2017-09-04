<?php

namespace Wikibase\Formatters;

use DataValues\MonolingualTextValue;
use InvalidArgumentException;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\LanguageNameLookup;

/**
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class MonolingualHtmlFormatter implements ValueFormatter {

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	public function __construct( LanguageNameLookup $languageNameLookup ) {
		$this->languageNameLookup = $languageNameLookup;
	}

	/**
	 * @see ValueFormatter::format
	 *
	 * @param MonolingualTextValue $value
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function format( $value ) {
		if ( !( $value instanceof MonolingualTextValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a MonolingualTextValue.' );
		}

		$text = $value->getText();
		$languageCode = $value->getLanguageCode();
		$languageName = $this->languageNameLookup->getName( $languageCode );

		return wfMessage( 'wikibase-monolingualtext',
			wfEscapeWikiText( $text ),
			wfEscapeWikiText( $languageCode ),
			wfEscapeWikiText( $languageName )
		)->parse();
	}

}

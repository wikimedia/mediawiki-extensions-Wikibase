<?php

namespace Wikibase\Lib\Formatters;

use DataValues\MonolingualTextValue;
use InvalidArgumentException;
use MediaWiki\Html\Html;
use MediaWiki\Language\LanguageCode;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\LanguageNameLookup;

/**
 * @license GPL-2.0-or-later
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

		return Html::rawElement(
			'span',
			[
				'lang' => LanguageCode::bcp47( $languageCode ),
				'class' => 'wb-monolingualtext-value',
			],
			// T442275: Same paranoia as in WikibaseValueFormatterBuilders::escapeValueFormatter
			htmlspecialchars( $text,
				// No point in escaping quotes in the contents of elements, same as Html::element
				ENT_NOQUOTES |
				// Replace invalid multi-byte sequences with replacement character (default)
				ENT_SUBSTITUTE |
				// T442275: Additionally, replace single-byte control characters with replacement character
				ENT_DISALLOWED |
				// T319219: Handle code as HTML 5 instead of the default HTML 4.01
				ENT_HTML5
			)
		) . wfMessage( 'word-separator' )->escaped() . Html::rawElement(
		'span',
			[
				'class' => 'wb-monolingualtext-language-name',
				'dir' => 'auto',
			],
			wfMessage( 'parentheses' )->plaintextParams( $languageName )->parse()
		);
	}

}

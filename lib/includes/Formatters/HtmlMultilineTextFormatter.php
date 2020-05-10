<?php

namespace Wikibase\Lib\Formatters;

use DataValues\StringValue;
use Html;
use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;

/**
 * Formats the StringValue from a "url" snak as an HTML link pointing to that URL.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class HtmlMultilineTextFormatter implements ValueFormatter {

	/**
	 * @param FormatterOptions|null $options
	 */
	public function __construct( FormatterOptions $options = null ) {
	}

	/**
	 * @see ValueFormatter::format
	 *
	 * Formats the given URL as an HTML link
	 *
	 * @param StringValue $value The URL to turn into a link
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function format( $value ) {
		if ( !( $value instanceof StringValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a StringValue.' );
		}

		$text = $value->getValue();

		$html = Html::element( 'pre', [ 'class' => 'multilinevalueview-instaticmode' ], $text );

		return $html;
	}

}

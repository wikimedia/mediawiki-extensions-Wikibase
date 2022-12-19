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
class HtmlUrlFormatter implements ValueFormatter {

	/**
	 * @var array HTML attributes to use on the generated <a> tags.
	 */
	protected $attributes;

	/**
	 * @param FormatterOptions|null $options
	 */
	public function __construct( FormatterOptions $options = null ) {
		// TODO: configure from options; see also HtmlExternalIdentifierFormatter
		$this->attributes = [
			'rel' => 'nofollow',
			'class' => 'external free',
		];
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

		$url = $value->getValue();

		$attributes = array_merge( $this->attributes, [ 'href' => $url ] );
		$html = Html::element( 'a', $attributes, $url );

		return $html;
	}

}

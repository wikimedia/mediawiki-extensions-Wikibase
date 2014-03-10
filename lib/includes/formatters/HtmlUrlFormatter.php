<?php

namespace Wikibase\Lib;

use DataValues\StringValue;
use Html;
use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;

/**
 * Formats a StringValue as an HTML link.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class HtmlUrlFormatter implements ValueFormatter {

	/**
	 * @var array HTML attributes to use on the generated <a> tags.
	 */
	protected $attributes;

	public function __construct( FormatterOptions $options ) {
		//TODO: configure from options
		$this->attributes = array(
			'rel' => 'nofollow',
			'class' => 'external free'
		);
	}

	/**
	 * Formats the given URL as an HTML link
	 *
	 * @since 0.5
	 *
	 * @param StringValue $value The URL to turn into a link
	 *
	 * @return string
	 *
	 * @throws InvalidArgumentException
	 */
	public function format( $value ) {
		if ( !( $value instanceof StringValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a StringValue.' );
		}

		$url = $value->getValue();

		$attributes = array_merge( $this->attributes, array( 'href' => $url ) );
		$html = Html::element( 'a', $attributes, $url );

		return $html;
	}

}

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
 * @todo Use MediaWiki renderer
 *
 * @licence GNU GPL v2+
 * @author Adrian Lang
 */
class CommonsLinkFormatter implements ValueFormatter {

	/**
	 * @var array HTML attributes to use on the generated <a> tags.
	 */
	protected $attributes;

	public function __construct( FormatterOptions $options ) {
		// @todo configure from options
		$this->attributes = array(
			'class' => 'extiw'
		);
	}

	/**
	 * Formats the given commons file name as an HTML link
	 *
	 * @since 0.5
	 *
	 * @param StringValue $value The commons file name to turn into a link
	 *
	 * @return string
	 *
	 * @throws InvalidArgumentException
	 */
	public function format( $value ) {
		if ( !( $value instanceof StringValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a StringValue.' );
		}

		$fileName = $value->getValue();

		$attributes = array_merge( $this->attributes, array( 'href' => '//commons.wikimedia.org/wiki/' . wfUrlencode( 'File:' . $fileName ) ) );
		$html = Html::element( 'a', $attributes, $fileName );

		return $html;
	}

}

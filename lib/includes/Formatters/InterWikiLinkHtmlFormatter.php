<?php

namespace Wikibase\Lib\Formatters;

use DataValues\StringValue;
use Html;
use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;

/**
 * Formats the StringValue from a snak as an HTML link.
 *
 * @license GPL-2.0+
 * @author Jonas Kress
 */
class InterWikiLinkHtmlFormatter implements ValueFormatter {

	/**
	 * @var array HTML attributes to use on the generated <a> tags.
	 */
	private $attributes = [ 'class' => 'extiw' ];

	/**
	 * @var string
	 */
	private $baseUrl;

	/**
	 * @param string $baseUrl
	 */
	public function __construct( $baseUrl ) {
		$this->baseUrl = $baseUrl;
	}

	/**
	 * @see ValueFormatter::format
	 *
	 * Formats the given page title as an HTML link
	 *
	 * @param StringValue $value The page title to  be turned into a link
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function format( $value ) {
		if ( !( $value instanceof StringValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a StringValue.' );
		}

		$attributes = array_merge( $this->attributes, array(
			'href' => $this->baseUrl . $this->getPathFromTitle( $value->getValue() )
		) );

		return Html::element( 'a', $attributes, $value->getValue() );

	}

	private function getPathFromTitle( $title ) {
		return urlencode( str_replace( ' ', '_', $title ) );

	}

}

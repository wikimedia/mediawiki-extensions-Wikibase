<?php

namespace Wikibase\Lib;

use DataValues\IriValue;
use DataValues\StringValue;
use Html;
use InvalidArgumentException;
use OutOfBoundsException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatterBase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;

/**
 * Formats a StringValue as an HTML link.
 *
 * @since 0.5
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class HtmlUrlFormatter extends ValueFormatterBase {

	public function __construct( FormatterOptions $options ) {
		parent::__construct( $options );

		//TODO: configure from options
		$this->attributes = array(
			'rel' => 'nofollow',
			'class' => 'external free'
		);
	}

	/**
	 * Formats the given URL as an HTML link
	 *
	 * @since 0.4
	 *
	 * @param StringValue|IriValue $value The URL to turn into a link
	 *
	 * @return string
	 *
	 * @throws InvalidArgumentException
	 * @throws OutOfBoundsException
	 */
	public function format( $value ) {
		if ( !( $value instanceof StringValue ) && !( $value instanceof IriValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a StringValue or IriValue.' );
		}

		$url = $value->getValue();

		$attributes = array_merge( $this->attributes, array( 'href' => $url ) );
		$html = Html::element( 'a', $attributes, $url );

		return $html;
	}

}

<?php

namespace Wikibase\formatters;

use DataValues\StringValue;
use InvalidArgumentException;
use ValueFormatters\ValueFormatter;

/**
 * Formats an identifier as a link.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class IdentifierFormatter implements ValueFormatter {

	/**
	 * @var ValueFormatter
	 */
	private $valueFormatter;

	/**
	 * @var string
	 */
	private $formatterUrl;

	public function __construct( ValueFormatter $valueFormatter, $formatterUrl ) {
		$this->valueFormatter = $valueFormatter;
		$this->formatterUrl = $formatterUrl;
	}

	/**
	 * Formats the given identifier as a link.
	 *
	 * @since 0.5
	 *
	 * @param StringValue $value The identifier to turn into a link
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function format( $value ) {
		if ( !( $value instanceof StringValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a StringValue.' );
		}

		$url = str_replace( '$1', $value, $this->formatterUrl );

		return $this->valueFormatter->format( new StringValue( $url ) );
	}

}

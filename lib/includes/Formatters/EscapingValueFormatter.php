<?php

namespace Wikibase\Lib\Formatters;

use InvalidArgumentException;
use ValueFormatters\ValueFormatter;

/**
 * EscapingValueFormatter wraps another ValueFormatter and
 * applies a transformation (escaping) to that formatter's output.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EscapingValueFormatter implements ValueFormatter {

	/**
	 * @var ValueFormatter
	 */
	private $formatter;

	/**
	 * @var callable
	 */
	private $escapeCallback;

	/**
	 * @param ValueFormatter $formatter A formatter returning plain text.
	 * @param callable $escapeCallback A callable taking plain text and returning escaped text.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( ValueFormatter $formatter, $escapeCallback ) {
		if ( !is_callable( $escapeCallback ) ) {
			throw new InvalidArgumentException( '$escapeCallback must be callable' );
		}

		$this->formatter = $formatter;
		$this->escapeCallback = $escapeCallback;
	}

	/**
	 * @see ValueFormatter::format
	 *
	 * @param mixed $value
	 *
	 * @return string Typically wikitext or HTML, depending on the $escapeCallback provided.
	 */
	public function format( $value ) {
		$text = $this->formatter->format( $value );
		$escaped = call_user_func( $this->escapeCallback, $text );
		return $escaped;
	}

}

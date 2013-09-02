<?php
namespace Wikibase\Lib;
use ValueFormatters\ValueFormatter;

/**
 * EscapingValueFormatter wraps another ValueFormatter and
 * applies a transformation (escaping) to that formatter's output.
 *
 * @license GPL 2+
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

	function __construct( ValueFormatter $formatter, $escapeCallback ) {
		if ( !is_callable( $escapeCallback ) ) {
			throw new \InvalidArgumentException( '$escapeCallback must be callable' );
		}

		$this->formatter = $formatter;
		$this->escapeCallback = $escapeCallback;
	}

	/**
	 * Formats a value.
	 *
	 * @since 0.1
	 *
	 * @param mixed $value The value to format
	 *
	 * @return mixed
	 */
	public function format( $value ) {
		$text = $this->formatter->format( $value );
		$escaped = call_user_func( $this->escapeCallback, $text );
		return $escaped;
	}
}
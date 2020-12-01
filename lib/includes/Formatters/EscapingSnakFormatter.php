<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Formatters;

use Wikibase\DataModel\Snak\Snak;
use Wikimedia\Assert\ParameterTypeException;

/**
 * EscapingSnakFormatter wraps another SnakFormatter and
 * applies a transformation (escaping) to that formatter's output.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EscapingSnakFormatter implements SnakFormatter {

	/**
	 * @var string One of the SnakFormatter::FORMAT_... constants.
	 */
	private $format;

	/**
	 * @var SnakFormatter
	 */
	private $formatter;

	/**
	 * @var callable
	 */
	private $escapeCallback;

	/**
	 * @param string $format One of the SnakFormatter::FORMAT_... constants.
	 * @param SnakFormatter $formatter A formatter returning plain text.
	 * @param callable $escapeCallback A callable taking plain text and returning escaped text.
	 *
	 * @throws ParameterTypeException
	 */
	public function __construct( string $format, SnakFormatter $formatter, callable $escapeCallback ) {
		$this->format = $format;
		$this->formatter = $formatter;
		$this->escapeCallback = $escapeCallback;
	}

	/**
	 * @see SnakFormatter::formatSnak
	 *
	 * @param Snak $snak
	 *
	 * @return string Typically wikitext or HTML, depending on the $escapeCallback provided.
	 */
	public function formatSnak( Snak $snak ) {
		$text = $this->formatter->formatSnak( $snak );
		$escaped = call_user_func( $this->escapeCallback, $text );
		return $escaped;
	}

	/**
	 * @see SnakFormatter::getFormat
	 *
	 * @return string One of the SnakFormatter::FORMAT_... constants.
	 */
	public function getFormat() {
		return $this->format;
	}

}

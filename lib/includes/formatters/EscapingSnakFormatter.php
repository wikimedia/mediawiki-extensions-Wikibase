<?php

namespace Wikibase\Lib;

use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Snak\Snak;
use Wikimedia\Assert\Assert;

/**
 * EscapingSnakFormatter wraps another SnakFormatter and
 * applies a transformation (escaping) to that formatter's output.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class EscapingSnakFormatter implements SnakFormatter {

	/**
	 * @var SnakFormatter
	 */
	private $formatter;

	/**
	 * @var callable
	 */
	private $escapeCallback;

	/**
	 * @var string
	 */
	private $format;

	/**
	 * @param string $format
	 * @param SnakFormatter $formatter
	 * @param callable $escapeCallback A callable taking plain text and returning escaped HTML
	 */
	public function __construct( $format, SnakFormatter $formatter, $escapeCallback ) {
		Assert::parameterType( 'string', $format, '$format' );
		Assert::parameterType( 'callable', $escapeCallback, '$escapeCallback' );

		$this->format = $format;
		$this->formatter = $formatter;
		$this->escapeCallback = $escapeCallback;
	}

	/**
	 * @see SnakFormatter::formatSnak
	 *
	 * @param Snak $snak
	 *
	 * @return string
	 */
	public function formatSnak( Snak $snak ) {
		$text = $this->formatter->formatSnak( $snak );
		$escaped = call_user_func( $this->escapeCallback, $text );
		return $escaped;
	}

	/**
	 * @see SnakFormatter::getFormat
	 *
	 * @return string
	 */
	public function getFormat() {
		return $this->format;
	}
}

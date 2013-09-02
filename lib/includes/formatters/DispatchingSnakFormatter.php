<?php
namespace Wikibase\Lib;
use InvalidArgumentException;
use Wikibase\Snak;

/**
 * DispatchingSnakFormatter will format a snak by delegating the formatting to an appropriate
 * SnakFormatter for the snak's type.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class DispatchingSnakFormatter implements SnakFormatter {

	/**
	 * @var SnakFormatter[] a map of snak type IDs to SnakFormatter objects
	 */
	private $formatters;

	/**
	 * @var string
	 */
	private $format;

	/**
	 * @param string $format
	 * @param SnakFormatter[] $formatters a map of snak type IDs to SnakFormatter objects
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( $format, array $formatters ) {
		if ( !is_string( $format ) ) {
			throw new InvalidArgumentException( '$format must be a string' );
		}

		foreach ( $formatters as $type => $formatter ) {
			if ( !is_string( $type ) ) {
				throw new InvalidArgumentException( '$formatters must map type IDs to formatters.' );
			}

			if ( !( $formatter instanceof SnakFormatter ) ) {
				throw new InvalidArgumentException( '$formatters must contain instances for SnakFormatter.' );
			}

			if ( $formatter->getFormat() !== $format ) {
				throw new InvalidArgumentException( 'The formatter supplied for ' . $type
						. ' returns ' . $formatter->getFormat() . ', but we expect ' . $format . '.' );
			}
		}

		$this->format = $format;
		$this->formatters = $formatters;

		//XXX: this should perhaps use, or be, a SnakFormatterFactory
	}

	/**
	 * Formats the given Snak by finding an appropriate formatter among the ones supplied
	 * to the constructor, and applying it.
	 *
	 * @param Snak $snak
	 *
	 * @throws FormattingException
	 * @return string
	 */
	public function formatSnak( Snak $snak ) {
		$type = $snak->getType();
		$formatter = $this->getFormatter( $type );

		if ( !$formatter ) {
			throw new FormattingException( "No formatter found for snak type $type" );
		}

		$text = $formatter->formatSnak( $snak );
		return $text;
	}

	/**
	 * @param $type
	 *
	 * @return null|SnakFormatter
	 */
	public function getFormatter( $type ) {
		if ( !isset( $this->formatters[$type] ) ) {
			return null;
		}

		return $this->formatters[$type];
	}

	/**
	 * @return string[]
	 */
	public function getSnakTypes() {
		return array_keys( $this->formatters );
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
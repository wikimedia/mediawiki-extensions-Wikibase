<?php

namespace Wikibase\Lib\Formatters;

use Html;
use InvalidArgumentException;
use Message;
use RuntimeException;
use Wikibase\DataModel\Snak\Snak;

/**
 * MessageSnakFormatter returns the same (localized) message for all snaks.
 * This is useful in cases where the output shall solely depend on the snak type,
 * e.g. for PropertyNoValueSnak or PropertySomeValueSnak.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class MessageSnakFormatter implements SnakFormatter {

	/**
	 * @var string[]
	 */
	private static $snakTypeCssClasses = [
		'somevalue' => 'wikibase-snakview-variation-somevaluesnak',
		'novalue' => 'wikibase-snakview-variation-novaluesnak',
	];

	/**
	 * @var string One of the SnakFormatter::FORMAT_... constants.
	 */
	private $format;

	/**
	 * @var Message
	 */
	private $message;

	/**
	 * @var string
	 */
	private $snakType;

	/**
	 * @param string $snakType Type of the snak, usually "value", "somevalue" or "novalue".
	 * @param Message $message
	 * @param string $format One of the SnakFormatter::FORMAT_... constants.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $snakType, Message $message, $format ) {
		if ( !is_string( $snakType ) ) {
			throw new InvalidArgumentException( '$snakType must be a string' );
		}

		if ( !is_string( $format ) ) {
			throw new InvalidArgumentException( '$format must be a string' );
		}

		$this->format  = $format;
		$this->message = $message;
		$this->snakType = $snakType;
	}

	/**
	 * @see SnakFormatter::getFormat
	 *
	 * @return string One of the SnakFormatter::FORMAT_... constants.
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * Returns a string from the message provided to the constructor.
	 * Depending on the desired format, the text is returned as plain, wikitext or HTML.
	 *
	 * Note that this method does not look at the snak given. It simply returns the same
	 * message always.
	 *
	 * @see SnakFormatter::formatSnak
	 *
	 * @param Snak $snak Unused in this implementation.
	 *
	 * @throws RuntimeException If the requested output format is not known.
	 * @return string Plain, wikitext or HTML
	 */
	public function formatSnak( Snak $snak ) {
		if ( $this->format === SnakFormatter::FORMAT_PLAIN ) {
			return $this->message->plain();
		} elseif ( $this->format === SnakFormatter::FORMAT_WIKI ) {
			return $this->message->text();
		} elseif ( strpos( $this->format, SnakFormatter::FORMAT_HTML ) === 0 ) {
			$html = $this->message->parse();

			if ( array_key_exists( $this->snakType, self::$snakTypeCssClasses ) ) {
				$html = Html::rawElement(
					'span',
					[ 'class' => self::$snakTypeCssClasses[$this->snakType] ],
					$html
				);
			}

			return $html;
		}

		throw new RuntimeException( 'Unknown format' );
	}

}

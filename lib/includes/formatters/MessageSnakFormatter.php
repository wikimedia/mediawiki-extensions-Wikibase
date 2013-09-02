<?php
namespace Wikibase\Lib;
use DataTypes\DataType;
use DataValues\DataValue;
use Message;
use Wikibase\Snak;

/**
 * MessageSnakFormatter returns the same (localized) message for all snaks.
 * This is useful in cases where the output shall solely depend on the snak type,
 * e.g. for PropertyNoValueSnak or PropertySomeValueSnak.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class MessageSnakFormatter implements SnakFormatter {

	/**
	 * @var string
	 */
	private $format;

	/**
	 * @var Message
	 */
	private $message;

	/**
	 * @param Message $message
	 * @param string   $format
	 *
	 * @throws \InvalidArgumentException
	 */
	function __construct( Message $message, $format ) {
		if ( !is_string( $format ) ) {
			throw new \InvalidArgumentException( '$format must be a string' );
		}

		$this->format  = $format;
		$this->message = $message;
	}

	/**
	 * Returns the format ID of the format this formatter generates.
	 * This uses the FORMAT_XXX constants defined in SnakFormatterFactory.
	 *
	 * @return string
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * Returns a string from the message provided to the constructor.
	 * Depending on the desired format, the text is returned as HTML or wikitext.
	 *
	 * @param Snak $snak
	 *
	 * @return string
	 */
	public function formatSnak( Snak $snak ) {
		if ( $this->format === SnakFormatterFactory::FORMAT_HTML
			|| $this->format === SnakFormatterFactory::FORMAT_HTML_WIDGET ) {
			$text = $this->message->parse();
		} else {
			$text = $this->message->text();
		}

		return $text;
	}
}
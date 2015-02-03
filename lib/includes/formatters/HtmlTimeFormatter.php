<?php

namespace Wikibase\Lib;

use DataValues\TimeValue;
use InvalidArgumentException;
use Language;
use Message;
use ValueFormatters\FormatterOptions;
use ValueFormatters\TimeFormatter;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;

/**
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 * @author Thiemo Mättig
 */
class HtmlTimeFormatter extends ValueFormatterBase {

	private static $calendarKeys = array(
		TimeFormatter::CALENDAR_GREGORIAN => 'gregorian',
		TimeFormatter::CALENDAR_JULIAN => 'julian',
	);

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var ValueFormatter
	 */
	private $dateTimeFormatter;

	/**
	 * @param FormatterOptions $options
	 * @param ValueFormatter $dateTimeFormatter
	 */
	public function __construct( FormatterOptions $options, ValueFormatter $dateTimeFormatter ) {
		$this->dateTimeFormatter = $dateTimeFormatter;

		$this->options = $options;

		$this->options->defaultOption( ValueFormatter::OPT_LANG, 'en' );

		$this->language = Language::factory(
			$this->options->getOption( ValueFormatter::OPT_LANG )
		);
	}

	/**
	 * Format a time data value
	 *
	 * @since 0.5
	 *
	 * @param TimeValue $value The time to format
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function format( $value ) {
		if ( !( $value instanceof TimeValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a TimeValue.' );
		}

		$formatted = $this->dateTimeFormatter->format( $value );

		if ( $this->calendarNameNeeded( $value ) ) {
			$formatted .= '<sup class="wb-calendar-name">'
				. $this->formatCalendarName( $value->getCalendarModel() )
				. '</sup>';
		}

		return $formatted;
	}

	/**
	 * @param TimeValue $value
	 *
	 * @return bool
	 */
	private function calendarNameNeeded( TimeValue $value ) {
		preg_match( '/^[-+]\d+/', $value->getTime(), $matches );
		$year = intval( $matches[0] );

		return $value->getPrecision() >= TimeValue::PRECISION_DAY && ( $year <= 1581
			|| $value->getCalendarModel() !== TimeFormatter::CALENDAR_GREGORIAN );
	}

	/**
	 * @param string $calendarModel
	 *
	 * @return string
	 */
	private function formatCalendarName( $calendarModel ) {
		if ( array_key_exists( $calendarModel, self::$calendarKeys ) ) {
			return $this->getMessage(
				'valueview-expert-timevalue-calendar-' . self::$calendarKeys[$calendarModel]
			);
		}

		return htmlspecialchars( $calendarModel );
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	private function getMessage( $key ) {
		$message = new Message( $key );
		$message->inLanguage( $this->language );
		return $message->text();
	}

}

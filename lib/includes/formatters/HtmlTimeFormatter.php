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
 */
class HtmlTimeFormatter extends ValueFormatterBase {

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

		$dateTime = $this->dateTimeFormatter->format( $value );
		$calendarName = $this->formatOptionalCalendarName( $value );
		return $dateTime . ( $calendarName ? " <sup class=\"wb-calendar-name\">$calendarName</sup>" : '' );
	}

	/**
	 * Display the calendar being used if the date lies within a time frame when
	 * multiple calendars have been in use or if the time value features a calendar that
	 * is uncommon for the specified time.
	 *
	 * @param TimeValue $value
	 * @return string
	 */
	private function formatOptionalCalendarName( TimeValue $value ) {
		return $this->calendarNameNeeded( $value ) ? $this->formatCalendarName( $value ) : '';
	}

	/**
	 * @param TimeValue $value
	 * @return bool
	 */
	private function calendarNameNeeded( TimeValue $value ) {
		preg_match( '/^[+-](\d+)-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/',
			$value->getTime(), $matches );
		$year = intval( $matches[1] );
		$calendar = $this->getCalendarKey( $value->getCalendarModel() );

		return $value->getPrecision() > 10 && ( $year <= 1581 || $calendar !== 'gregorian' );
	}

	/**
	 * @param TimeValue $value
	 * @return string
	 */
	private function formatCalendarName( TimeValue $value ) {
		$calendarKey = $this->getCalendarKey( $value->getCalendarModel() );
		return $this->getMessage( 'valueview-expert-timevalue-calendar-' . $calendarKey );
	}

	/**
	 * @param string $uri
	 * @return string
	 */
	private function getCalendarKey( $uri ) {
		$calendars = array(
		TimeFormatter::CALENDAR_GREGORIAN => 'gregorian',
			TimeFormatter::CALENDAR_JULIAN => 'julian',
		);
		return $calendars[ $uri ];
	}

	/**
	 * @param string $key
	 * @return string
	 */
	private function getMessage( $key ) {
		$message = new Message( $key );
		$message->inLanguage( $this->language );
		return $message->text();
	}
}

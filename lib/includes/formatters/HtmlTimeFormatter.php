<?php

namespace Wikibase\Lib;

use DataValues\TimeValue;
use InvalidArgumentException;
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
		TimeFormatter::CALENDAR_GREGORIAN => 'valueview-expert-timevalue-calendar-gregorian',
		TimeFormatter::CALENDAR_JULIAN => 'valueview-expert-timevalue-calendar-julian',
	);

	/**
	 * @var ValueFormatter
	 */
	private $dateTimeFormatter;

	/**
	 * @param FormatterOptions $options
	 * @param ValueFormatter $dateTimeFormatter
	 */
	public function __construct( FormatterOptions $options, ValueFormatter $dateTimeFormatter ) {
		parent::__construct( $options );

		$this->dateTimeFormatter = $dateTimeFormatter;
	}

	/**
	 * Format a time data value
	 *
	 * @since 0.5
	 *
	 * @param TimeValue $value The time to format
	 *
	 * @return string HTML
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

		// This is how the original JavaScript UI decided this:
		// year <= 1581 && calendar === 'Gregorian' ||
		// year > 1581 && year < 1930 ||
		// year >= 1930 && calendar === 'Julian'
		return $value->getPrecision() >= TimeValue::PRECISION_DAY && (
			$year <= 1581 || $value->getCalendarModel() !== TimeFormatter::CALENDAR_GREGORIAN
		);
	}

	/**
	 * @param string $calendarModel
	 *
	 * @return string HTML
	 */
	private function formatCalendarName( $calendarModel ) {
		if ( array_key_exists( $calendarModel, self::$calendarKeys ) ) {
			$key = self::$calendarKeys[$calendarModel];
			$lang = $this->getOption( ValueFormatter::OPT_LANG );
			$msg = wfMessage( $key )->inLanguage( $lang );

			if ( $msg->exists() ) {
				return htmlspecialchars( $msg->text() );
			}
		}

		return htmlspecialchars( $calendarModel );
	}

}

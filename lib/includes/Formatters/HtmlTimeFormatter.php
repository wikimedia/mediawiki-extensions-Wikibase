<?php

namespace Wikibase\Lib\Formatters;

use DataValues\TimeValue;
use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;

/**
 * A value formatter that creates a basic, single-line HTML representation of a TimeValue's date,
 * time and calendar model. The calendar model is added in superscript when needed,
 * as determined by the {@link ShowCalendarModelDecider} (taking the options into account).
 *
 * @see \Wikibase\Lib\Formatters\TimeDetailsFormatter
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Thiemo Kreuz
 * @author Daniel Kinzler
 */
class HtmlTimeFormatter implements ValueFormatter {

	private const CALENDAR_KEYS = [
		TimeValue::CALENDAR_GREGORIAN => 'wikibase-time-calendar-gregorian',
		TimeValue::CALENDAR_JULIAN => 'wikibase-time-calendar-julian',
	];

	/**
	 * @var ValueFormatter
	 */
	private $dateTimeFormatter;

	/**
	 * @var FormatterOptions
	 */
	private $options;

	private ShowCalendarModelDecider $decider;

	/**
	 * @param FormatterOptions|null $options
	 * @param ValueFormatter $dateTimeFormatter A value formatter that accepts TimeValue objects and
	 *  returns the formatted date and time, but not the calendar model.
	 *  The formatter is assumed to return plain text (its output will be HTML-escaped).
	 * @param ShowCalendarModelDecider $decider
	 */
	public function __construct(
		?FormatterOptions $options,
		ValueFormatter $dateTimeFormatter,
		ShowCalendarModelDecider $decider
	) {
		$this->options = $options ?: new FormatterOptions();
		$this->options->defaultOption( ValueFormatter::OPT_LANG, 'en' );
		$this->options->defaultOption( ShowCalendarModelDecider::OPT_SHOW_CALENDAR, 'auto' );

		$this->dateTimeFormatter = $dateTimeFormatter;
		$this->decider = $decider;
	}

	/**
	 * @see ValueFormatter::format
	 *
	 * @param TimeValue $value
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function format( $value ) {
		if ( !( $value instanceof TimeValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a TimeValue.' );
		}

		$formatted = htmlspecialchars( $this->dateTimeFormatter->format( $value ) );

		if ( $this->decider->showCalendarModel( $value, $this->options ) ) {
			$formatted .= '<sup class="wb-calendar-name">'
				. $this->formatCalendarName( $value->getCalendarModel() )
				. '</sup>';
		}

		return $formatted;
	}

	/**
	 * @param string $calendarModel
	 *
	 * @return string HTML
	 */
	private function formatCalendarName( $calendarModel ) {
		if ( array_key_exists( $calendarModel, self::CALENDAR_KEYS ) ) {
			$key = self::CALENDAR_KEYS[$calendarModel];
			$lang = $this->options->getOption( ValueFormatter::OPT_LANG );
			$msg = wfMessage( $key )->inLanguage( $lang );

			if ( $msg->exists() ) {
				return htmlspecialchars( $msg->text() );
			}
		}

		return htmlspecialchars( $calendarModel );
	}

}

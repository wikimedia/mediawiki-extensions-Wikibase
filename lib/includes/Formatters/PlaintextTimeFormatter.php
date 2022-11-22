<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Formatters;

use DataValues\TimeValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;

/**
 * A value formatter that formats a time value as plain text,
 * including the calendar model if necessary or specified by the formatter options.
 * The calendar model is added in parentheses when needed,
 * as determined by the {@link ShowCalendarModelDecider} (taking the options into account).
 *
 * @license GPL-2.0-or-later
 */
class PlaintextTimeFormatter implements ValueFormatter {

	private const CALENDAR_KEYS = [
		TimeValue::CALENDAR_GREGORIAN => 'wikibase-time-calendar-gregorian',
		TimeValue::CALENDAR_JULIAN => 'wikibase-time-calendar-julian',
	];

	private FormatterOptions $options;
	private ValueFormatter $dateTimeFormatter;
	private ShowCalendarModelDecider $decider;

	/**
	 * @param FormatterOptions|null $options
	 * @param ValueFormatter $dateTimeFormatter A value formatter that accepts TimeValue objects and
	 *  returns the formatted date and time, but not the calendar model.
	 * @param ShowCalendarModelDecider $decider
	 */
	public function __construct(
		?FormatterOptions $options,
		ValueFormatter $dateTimeFormatter,
		ShowCalendarModelDecider $decider
	) {
		$this->options = $options ?: new FormatterOptions();
		$this->options->defaultOption( ValueFormatter::OPT_LANG, 'en' );
		// for backwards compatibility with older versions, of Wikibase,
		// never show the calendar model by default (users have to opt into 'auto')
		$this->options->defaultOption( ShowCalendarModelDecider::OPT_SHOW_CALENDAR, false );

		$this->dateTimeFormatter = $dateTimeFormatter;
		$this->decider = $decider;
	}

	public function format( $value ): string {
		$formatted = $this->dateTimeFormatter->format( $value );

		if ( $this->decider->showCalendarModel( $value, $this->options ) ) {
			$formatted = wfMessage( 'wikibase-time-with-calendar' )
				->inLanguage( $this->options->getOption( ValueFormatter::OPT_LANG ) )
				->plaintextParams(
					$formatted,
					$this->formatCalendarName( $value->getCalendarModel() )
				)->text();
		}

		return $formatted;
	}

	private function formatCalendarName( string $calendarModel ): string {
		if ( array_key_exists( $calendarModel, self::CALENDAR_KEYS ) ) {
			$key = self::CALENDAR_KEYS[$calendarModel];
			$lang = $this->options->getOption( ValueFormatter::OPT_LANG );
			$msg = wfMessage( $key )->inLanguage( $lang );

			if ( $msg->exists() ) {
				return $msg->text();
			}
		}

		return $calendarModel;
	}

}

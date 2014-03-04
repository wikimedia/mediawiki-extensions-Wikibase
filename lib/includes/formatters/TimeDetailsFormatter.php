<?php

namespace Wikibase\Lib;

use DataValues\TimeValue;
use Html;
use InvalidArgumentException;
use Message;
use ValueFormatters\FormatterOptions;
use ValueFormatters\TimeFormatter;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;

/**
 * Formatter for rendering the details of a TimeValue (most useful for diffs) in HTML.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class TimeDetailsFormatter extends ValueFormatterBase {

	/**
	 * @var MwTimeIsoFormatter
	 */
	protected $isoTimeFormatter;

	/**
	 * @var TimeFormatter
	 */
	protected $timeFormatter;

	/**
	 * @param FormatterOptions $options
	 */
	public function __construct( FormatterOptions $options ) {
		parent::__construct( $options );

		if ( $options->hasOption( TimeFormatter::OPT_TIME_ISO_FORMATTER ) ) {
			$this->isoTimeFormatter = $options->getOption( TimeFormatter::OPT_TIME_ISO_FORMATTER );
		} else {
			$this->isoTimeFormatter = new MwTimeIsoFormatter( $options );
			$options->setOption( TimeFormatter::OPT_TIME_ISO_FORMATTER, $this->isoTimeFormatter );
		}

		$this->timeFormatter = new TimeFormatter( $options );
	}

	/**
	 * Generates HTML representing the details of a TimeValue,
	 * as an itemized list.
	 *
	 * @since 0.5
	 *
	 * @param TimeValue $value The ID to format
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function format( $value ) {
		if ( !( $value instanceof TimeValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected an TimeValue.' );
		}

		$html = '';
		$html .= Html::element( 'h4',
			array( 'class' => 'wikibase-details wikibase-time-details wikibase-time-rendered' ),
			$this->timeFormatter->format( $value )
		);

		$html .= Html::openElement( 'dl', array( 'class' => 'wikibase-details wikibase-time-details' ) );
		$html .= $this->renderLabelValuePair( 'isotime', htmlspecialchars( strval( $value->getTime() ) ) );

		//TODO: provide "nice" rendering of timezone, calendar, precision, etc.
		$html .= $this->renderLabelValuePair( 'timezone', htmlspecialchars( strval( $value->getTimezone() ) ) );
		$html .= $this->renderLabelValuePair( 'calendar', htmlspecialchars( strval( $value->getCalendarModel() ) ) );
		$html .= $this->renderLabelValuePair( 'precision', htmlspecialchars( strval( $value->getPrecision() ) ) );

		$html .= $this->renderLabelValuePair( 'before', htmlspecialchars( strval( $value->getBefore() ) ) );
		$html .= $this->renderLabelValuePair( 'after', htmlspecialchars( strval( $value->getAfter() ) ) );

		$html .= Html::closeElement( 'dl' );

		return $html;
	}

	/**
	 * @param string $fieldName
	 * @param string $valueHtml
	 *
	 * @return string HTML for the label/value pair
	 */
	protected function renderLabelValuePair( $fieldName, $valueHtml ) {
		$html = '';
		$html .= Html::element( 'dt', array( 'class' => 'wikibase-time-' . $fieldName ), $this->getFieldLabel( $fieldName )->text() );
		$html .= Html::element( 'dd', array( 'class' => 'wikibase-time-' . $fieldName ), $valueHtml );

		return $html;
	}

	/**
	 * @param string $fieldName
	 *
	 * @return Message
	 */
	protected function getFieldLabel( $fieldName ) {
		$lang = $this->getOption( ValueFormatter::OPT_LANG );

		// Messages: wikibase-timedetails-amount, wikibase-timedetails-upperbound,
		// wikibase-timedetails-lowerbound, wikibase-timedetails-unit
		$key = 'wikibase-timedetails-' . strtolower( $fieldName );
		$msg = wfMessage( $key )->inLanguage( $lang );

		return $msg;
	}
}

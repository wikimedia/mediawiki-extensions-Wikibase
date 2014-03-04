<?php

namespace Wikibase\Lib;

use DataValues\GlobeCoordinateValue;
use Html;
use InvalidArgumentException;
use Message;
use NumberFormatter;
use ValueFormatters\DecimalFormatter;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;

/**
 * Formatter for rendering the details of a GlobeCoordinateValue (most useful for diffs) in HTML.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class GlobeCoordinateDetailsFormatter extends ValueFormatterBase {

	/**
	 * @param FormatterOptions $options
	 */
	public function __construct( FormatterOptions $options ) {
		parent::__construct( $options );
	}

	/**
	 * Generates HTML representing the details of a GlobeCoordinateValue,
	 * as an itemized list.
	 *
	 * @since 0.5
	 *
	 * @param GlobeCoordinateValue $value The ID to format
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function format( $value ) {
		if ( !( $value instanceof GlobeCoordinateValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected an GlobeCoordinateValue.' );
		}

		$html = '';
		$html .= Html::openElement( 'dl', array( 'class' => 'wikibase-details wikibase-globe-details' ) );

		//TODO: nicer formatting and localization of numbers.
		$html .= $this->renderLabelValuePair( 'latitude', htmlspecialchars( strval( $value->getLatitude() ) ) );
		$html .= $this->renderLabelValuePair( 'longitude', htmlspecialchars( strval( $value->getLongitude() ) ) );
		$html .= $this->renderLabelValuePair( 'precision', htmlspecialchars( strval( $value->getPrecision() ) ) );
		$html .= $this->renderLabelValuePair( 'globe', htmlspecialchars( strval( $value->getGlobe() ) ) );

		$html .= Html::closeElement( 'dl' );

		return $html;
	}

	/**
	 * @param string $fieldName
	 * @param string $valueHtml
	 *
	 * @return string HTML for the label/value pair
	 */
	public function renderLabelValuePair( $fieldName, $valueHtml ) {
		$html = '';
		$html .= Html::element( 'dt', array( 'class' => 'wikibase-globe-' . $fieldName ), $this->getFieldLabel( $fieldName )->text() );
		$html .= Html::element( 'dd', array( 'class' => 'wikibase-globe-' . $fieldName ), $valueHtml );

		return $html;
	}

	/**
	 * @param string $fieldName
	 *
	 * @return Message
	 */
	protected function getFieldLabel( $fieldName ) {
		$lang = $this->getOption( ValueFormatter::OPT_LANG );

		// Messages: wikibase-globedetails-amount, wikibase-globedetails-upperbound,
		// wikibase-globedetails-lowerbound, wikibase-globedetails-unit
		$key = 'wikibase-globedetails-' . strtolower( $fieldName );
		$msg = wfMessage( $key )->inLanguage( $lang );

		return $msg;
	}
}

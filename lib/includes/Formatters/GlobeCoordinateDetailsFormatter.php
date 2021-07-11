<?php

namespace Wikibase\Lib\Formatters;

use DataValues\Geo\Formatters\GlobeCoordinateFormatter;
use DataValues\Geo\Formatters\LatLongFormatter;
use DataValues\Geo\Values\GlobeCoordinateValue;
use Html;
use InvalidArgumentException;
use Message;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;

/**
 * Formatter for rendering the details of a GlobeCoordinateValue (most useful for diffs) in HTML.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class GlobeCoordinateDetailsFormatter implements ValueFormatter {

	/**
	 * @var GlobeCoordinateFormatter
	 */
	protected $coordinateFormatter;

	/**
	 * @var ValueFormatter
	 */
	protected $vocabularyUriFormatter;

	/**
	 * @var FormatterOptions
	 */
	private $options;

	/**
	 * @param ValueFormatter $vocabularyUriFormatter
	 * @param FormatterOptions|null $options
	 */
	public function __construct(
		ValueFormatter $vocabularyUriFormatter,
		FormatterOptions $options = null
	) {
		$this->options = $options ?: new FormatterOptions();

		// TODO: What's a good default? Should this be locale dependant? Configurable?
		$this->options->defaultOption( LatLongFormatter::OPT_FORMAT, LatLongFormatter::TYPE_DMS );

		$this->coordinateFormatter = new GlobeCoordinateFormatter( $this->options );

		$this->vocabularyUriFormatter = $vocabularyUriFormatter;
	}

	/**
	 * @see ValueFormatter::format
	 *
	 * Generates HTML representing the details of a GlobeCoordinateValue,
	 * as an itemized list.
	 *
	 * @param GlobeCoordinateValue $value
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function format( $value ) {
		if ( !( $value instanceof GlobeCoordinateValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a GlobeCoordinateValue.' );
		}

		$html = '';
		$html .= Html::element( 'b',
			[ 'class' => 'wb-details wb-globe-details wb-globe-rendered' ],
			$this->coordinateFormatter->format( $value )
		);

		$html .= Html::openElement( 'table',
			[ 'class' => 'wb-details wb-globe-details' ] );

		//TODO: nicer formatting and localization of numbers.
		$html .= $this->renderLabelValuePair( 'latitude',
			$value->getLatitude() );
		$html .= $this->renderLabelValuePair( 'longitude',
			$value->getLongitude() );
		$html .= $this->renderLabelValuePair( 'precision',
			$value->getPrecision() );
		$html .= $this->renderLabelValuePair( 'globe',
			$this->formatGlobe( $value->getGlobe() ) );

		$html .= Html::closeElement( 'table' );

		return $html;
	}

	/**
	 * @param string $globe URI
	 *
	 * @return string HTML
	 */
	private function formatGlobe( $globe ) {
		$formattedGlobe = $this->vocabularyUriFormatter->format( $globe );

		if ( $formattedGlobe === null || $formattedGlobe === $globe ) {
			return htmlspecialchars( $globe );
		}

		return Html::element( 'a', [ 'href' => $globe ], $formattedGlobe );
	}

	/**
	 * @param string $fieldName
	 * @param string $valueHtml HTML
	 *
	 * @return string HTML for the label/value pair
	 */
	protected function renderLabelValuePair( $fieldName, $valueHtml ) {
		$html = Html::openElement( 'tr' );

		$html .= Html::element( 'th', [ 'class' => 'wb-globe-' . $fieldName ],
			$this->getFieldLabel( $fieldName )->text() );
		$html .= Html::rawElement( 'td', [ 'class' => 'wb-globe-' . $fieldName ],
			$valueHtml );

		$html .= Html::closeElement( 'tr' );
		return $html;
	}

	/**
	 * @param string $fieldName
	 *
	 * @return Message
	 */
	protected function getFieldLabel( $fieldName ) {
		$lang = $this->options->getOption( ValueFormatter::OPT_LANG );

		// Messages:
		// wikibase-globedetails-latitude
		// wikibase-globedetails-longitude
		// wikibase-globedetails-precision
		// wikibase-globedetails-globe
		$key = 'wikibase-globedetails-' . strtolower( $fieldName );
		$msg = wfMessage( $key )->inLanguage( $lang );

		return $msg;
	}

}

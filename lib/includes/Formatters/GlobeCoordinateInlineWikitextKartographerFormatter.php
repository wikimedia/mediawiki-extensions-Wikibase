<?php

namespace Wikibase\Lib\Formatters;

use DataValues\Geo\Values\GlobeCoordinateValue;
use Html;
use InvalidArgumentException;
use ValueFormatters\ValueFormatter;

/**
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class GlobeCoordinateInlineWikitextKartographerFormatter implements ValueFormatter {

	/**
	 * @var ValueFormatter
	 */
	private $coordinateFormatter;

	/**
	 * @param ValueFormatter $globeCoordinateFormatter
	 */
	public function __construct(
		ValueFormatter $globeCoordinateFormatter
	) {
		$this->coordinateFormatter = $globeCoordinateFormatter;
	}

	/**
	 * @see ValueFormatter::format
	 *
	 * @param GlobeCoordinateValue $value
	 *
	 * @throws InvalidArgumentException
	 * @return string Wikitext
	 */
	public function format( $value ) {
		if ( !( $value instanceof GlobeCoordinateValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a GlobeCoordinateValue.' );
		}

		if ( $value->getGlobe() === GlobeCoordinateValue::GLOBE_EARTH ) {
			return $this->formatEarthCoordinate( $value );
		}

		return wfEscapeWikiText( $this->coordinateFormatter->format( $value ) );
	}

	private function formatEarthCoordinate( GlobeCoordinateValue $value ) {
		return Html::rawElement( 'maplink', [
			'latitude' => $value->getLatitude(),
			'longitude' => $value->getLongitude(),
		], json_encode( [
			'type' => 'Feature',
			'geometry' => [ 'type' => 'Point', 'coordinates' => [ $value->getLongitude(), $value->getLatitude() ] ],
		] ) );
	}

}

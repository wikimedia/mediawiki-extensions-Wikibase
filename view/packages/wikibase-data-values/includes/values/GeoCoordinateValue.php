<?php

namespace DataValues;

use InvalidArgumentException;

/**
 * Class representing a geographical coordinate value.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.1
 *
 * @file
 * @ingroup DataValue
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class GeoCoordinateValue extends DataValueObject {

	/**
	 * The locations latitude.
	 *
	 * @since 0.1
	 *
	 * @var float
	 */
	protected $latitude;

	/**
	 * The locations longitude.
	 *
	 * @since 0.1
	 *
	 * @var float
	 */
	protected $longitude;

	/**
	 * The locations altitude or null if it's not known.
	 *
	 * @since 0.1
	 *
	 * @var float|null
	 */
	protected $altitude;

	/**
	 * The globe on which the location resides.
	 *
	 * @since 0.1
	 *
	 * @var string|null
	 */
	protected $globe;

	/**
	 * The precision of the coordinate.
	 *
	 * @since 0.1
	 *
	 * @var float|null
	 */
	protected $precision;

	/**
	 * @since 0.1
	 *
	 * @param float|int $latitude
	 * @param float|int $longitude
	 * @param float|int|null $altitude
	 * @param string|null $globe
	 * @param float|int|null $precision
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $latitude, $longitude, $altitude = null, $globe = 'http://www.wikidata.org/entity/Q2', $precision = null ) {
		// TODO: validate those values!
		if ( is_int( $latitude ) ) {
			$latitude = (float)$latitude;
		}

		if ( is_int( $longitude ) ) {
			$longitude = (float)$longitude;
		}

		if ( is_int( $altitude ) ) {
			$altitude = (float)$altitude;
		}

		if( is_int( $precision ) ) {
			$precision = (float)$precision;
		}

		if ( !is_float( $latitude ) ) {
			throw new InvalidArgumentException( 'Can only construct GeoCoordinateValue with a numeric latitude' );
		}

		if ( !is_float( $longitude ) ) {
			throw new InvalidArgumentException( 'Can only construct GeoCoordinateValue with a numeric longitude' );
		}

		if ( $altitude !== null && !is_float( $altitude ) ) {
			throw new InvalidArgumentException( 'Can only construct GeoCoordinateValue with a numeric altitude' );
		}

		if ( $precision !== null && !is_float( $precision ) ) {
			throw new InvalidArgumentException( 'Can only construct GeoCoordinateValue with a numeric precision' );
		}

		if ( !is_string( $globe ) && $globe !== null ) {
			throw new InvalidArgumentException( 'Can only construct GeoCoordinateValue with a string or null globe parameter' );
		}

		$this->latitude = $latitude;
		$this->longitude = $longitude;
		$this->altitude = $altitude;
		$this->globe = $globe;
		$this->precision = $precision;
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function serialize() {
		return json_encode( array_values( $this->getArrayValue() ) );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param string $value
	 *
	 * @return GeoCoordinateValue
	 * @throws InvalidArgumentException
	 */
	public function unserialize( $value ) {
		list( $latitude, $longitude, $altitude, $globe, $precision ) = json_decode( $value );
		$this->__construct( $latitude, $longitude, $altitude, $globe, $precision );
	}

	/**
	 * @see DataValue::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public static function getType() {
		return 'globecoordinate';
	}

	/**
	 * @see DataValue::getSortKey
	 *
	 * @since 0.1
	 *
	 * @return string|float|int
	 */
	public function getSortKey() {
		return $this->latitude;
	}

	/**
	 * Returns the text.
	 * @see DataValue::getValue
	 *
	 * @since 0.1
	 *
	 * @return GeoCoordinateValue
	 */
	public function getValue() {
		return $this;
	}

	/**
	 * Returns the latitude.
	 *
	 * @since 0.1
	 *
	 * @return float
	 */
	public function getLatitude() {
		return $this->latitude;
	}

	/**
	 * Returns the longitude.
	 *
	 * @since 0.1
	 *
	 * @return float
	 */
	public function getLongitude() {
		return $this->longitude;
	}

	/**
	 * Returns the altitude.
	 *
	 * @since 0.1
	 *
	 * @return float|null
	 */
	public function getAltitude() {
		return $this->altitude;
	}

	/**
	 * Returns the globe on which the location resides.
	 *
	 * @since 0.1
	 *
	 * @return string|null
	 */
	public function getGlobe() {
		return $this->globe;
	}

	/**
	 * Returns the precision of the coordinate.
	 *
	 * TODO: Introduce some constants holding the different precisions and document. Sync with JS.
	 *  Take Time as an example how to do this.
	 *
	 * @since 0.1
	 *
	 * @return float|null
	 */
	public function getPrecision() {
		return $this->precision;
	}

	/**
	 * @see DataValue::getArrayValue
	 *
	 * @since 0.1
	 *
	 * @return mixed
	 */
	public function getArrayValue() {
		return array(
			'latitude' => $this->latitude,
			'longitude' => $this->longitude,
			'altitude' => $this->altitude,
			'globe' => $this->globe,
			'precision' => $this->precision
		);
	}

	/**
	 * Constructs a new instance of the DataValue from the provided data.
	 * This can round-trip with @see getArrayValue
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return GeoCoordinateValue
	 */
	public static function newFromArray( array $data ) {
		return new static(
			$data['latitude'],
			$data['longitude'],
			$data['altitude'],
			$data['globe'],
			$data['precision']
		);
	}

}

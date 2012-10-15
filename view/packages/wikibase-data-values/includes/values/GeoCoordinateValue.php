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
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param float|int $latitude
	 * @param float|int $longitude
	 * @param float|null $altitude
	 * @param string|null $globe
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $latitude, $longitude, $altitude = null, $globe = 'earth' ) {
		if ( is_int( $latitude ) ) {
			$latitude = (float)$latitude;
		}

		if ( is_int( $longitude ) ) {
			$longitude = (float)$longitude;
		}

		if ( is_int( $altitude ) ) {
			$altitude = (float)$altitude;
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

		if ( !is_string( $globe ) && $globe !== null ) {
			throw new InvalidArgumentException( 'Can only construct GeoCoordinateValue with a string or null globe parameter' );
		}

		$this->latitude = $latitude;
		$this->longitude = $longitude;
		$this->altitude = $altitude;

		$this->globe = $globe;

	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function serialize() {
		$data = array(
			$this->latitude,
			$this->longitude
		);

		if ( $this->globe !== 'earth' || $this->altitude !== null ) {
			$data[] = $this->altitude;
		}

		if ( $this->globe !== 'earth' ) {
			$data[] = $this->globe;
		}

		return implode( '|', $data );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param string $value
	 *
	 * @return MonolingualTextValue
	 */
	public function unserialize( $value ) {
		$data = explode( '|', $value, 4 );

		$this->__construct(
			(float)$data[0],
			(float)$data[1],
			array_key_exists( 2, $data ) ? (float)$data[2] : null,
			array_key_exists( 3, $data ) ? $data[3] : 'earth'
		);
	}

	/**
	 * @see DataValue::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return 'geocoordinate';
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
		);
	}

}

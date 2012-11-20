<?php

namespace Wikibase;
use MWException;

/**
 * Base class for snaks.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Snaks
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
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class SnakObject implements Snak {

	/**
	 * @since 0.1
	 *
	 * @var EntityId
	 */
	protected $propertyId;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param EntityId $propertyId
	 *
	 * @throws MWException
	 */
	public function __construct( $propertyId ) {
		if ( !$propertyId instanceof EntityId ) {
			throw new MWException( '$propertyId should be a EntityId' );
		}

		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new MWException( 'The $propertyId of a property snak can only be an ID of a Property object' );
		}

		$this->propertyId = $propertyId;
	}

	/**
	 * @see Snak::getPropertyId
	 *
	 * @since 0.1
	 *
	 * @return EntityId
	 */
	public function getPropertyId() {
		return $this->propertyId;
	}

	/**
	 * @see Snak::getHash
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHash() {
		return sha1( serialize( $this ) );
	}

	/**
	 * @see Comparable::equals
	 *
	 * @since 0.3
	 *
	 * @param mixed $target
	 *
	 * @return boolean
	 */
	public function equals( $target ) {
		if ( is_object( $target ) && ( $target instanceof Snak ) ) {
			return $this->getHash() === $target->getHash();
		}

		return false;
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize( $this->propertyId->getNumericId() );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param string $serialized
	 *
	 * @return Snak
	 */
	public function unserialize( $serialized ) {
		$this->propertyId = new EntityId( Property::ENTITY_TYPE, (int)unserialize( $serialized ) );
	}

	/**
	 * @see Snak::getSerialization
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public final function getSerialization() {
		return json_encode( $this->getSerializationData() );
	}

	/**
	 * Returns the data needed by @see getSerialization in an array.
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	protected function getSerializationData() {
		$data = array();

		$data[] = $this->getType();
		$data[] = $this->getPropertyId()->getNumericId();

		return $data;
	}

	/**
	 * Factory for constructing Snak objects from internal serialization.
	 *
	 * Expected format is a json_encoded array with
	 * - snak type (string)
	 * - property id (int)
	 * [
	 * - data value (mixed)
	 * - data value type (string)
	 * ]
	 *
	 * @since 0.3
	 *
	 * @param string $serialization
	 *
	 * @return Snak
	 */
	public static function newFromSerialization( $serialization ) {
		$data = json_decode( $serialization, true );

		$snakJar = array(
			'value' => '\Wikibase\PropertyValueSnak',
			'novalue' => '\Wikibase\PropertyNoValueSnak',
			'somevalue' => '\Wikibase\PropertySomeValueSnak',
		);

		$reflector = new \ReflectionClass( $snakJar[array_shift( $data )] );

		$data[0] = new EntityId( Property::ENTITY_TYPE, $data[0] );

		if ( count( $data ) > 1 ) {
			$data[1] = \DataValues\DataValueFactory::singleton()->newDataValue( $data[1], $data[2] );
			unset( $data[2] );
		}

		$instance = $reflector->newInstanceArgs( $data );

		return $instance;
	}

}

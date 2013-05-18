<?php

namespace Wikibase;

use DataValues\DataValue;
use MWException;

/**
 * Class representing a property value snak.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#PropertyValueSnak
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
 * @ingroup WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyValueSnak extends SnakObject {

	/**
	 * @since 0.1
	 *
	 * @var DataValue
	 */
	protected $dataValue;

	/**
	 * @since 0.1
	 *
	 * @param EntityId|integer $propertyId
	 * @param DataValue $dataValue
	 */
	public function __construct( $propertyId, DataValue $dataValue ) {
		parent::__construct( $propertyId );
		$this->dataValue = $dataValue;
	}

	/**
	 * Returns the value of the property value snak.
	 *
	 * @since 0.1
	 *
	 * @return DataValue
	 */
	public function getDataValue() {
		return $this->dataValue;
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize( array( $this->propertyId->getNumericId(), $this->dataValue ) );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param string $serialized
	 *
	 * @return PropertyValueSnak
	 */
	public function unserialize( $serialized ) {
		list( $propertyId, $dataValue ) = unserialize( $serialized );

		$this->__construct(
			new EntityId( Property::ENTITY_TYPE, $propertyId ),
			$dataValue
		);
	}

	/**
	 * @see Snak::toArray
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function toArray() {
		$data = parent::toArray();

		$data[] = $this->dataValue->getType();
		$data[] = $this->dataValue->getArrayValue();

		return $data;
	}

	/**
	 * @see Snak::getType
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getType() {
		return 'value';
	}

	/**
	 * Returns a new PropertyValueSnak constructed from the provided value.
	 * The DataValue
	 *
	 * @since 0.3
	 * @deprecated since 0.4
	 *
	 * @param EntityId $propertyId
	 * @param mixed $rawDataValue
	 *
	 * @return PropertyValueSnak
	 * @throws MWException
	 */
	public static function newFromPropertyValue( EntityId $propertyId, $rawDataValue ) {
		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new MWException( 'Expected an EntityId of a property' );
		}

		$content = EntityContentFactory::singleton()->getFromId( $propertyId );

		if ( $content === null ) {
			throw new MWException( 'Cannot create a DataValue for a non-existing property' );
		}

		/**
		 * @var Property $property
		 */
		$property = $content->getEntity();

		$dataValue = $property->newDataValue( $rawDataValue );

		return new static( $propertyId, $dataValue );
	}

}
<?php

namespace Wikibase;

/**
 * Class representing a broken property value snak.
 * This can be used to represent PropertyValueSnaks that could not be instantiated because
 * of corrupt data.
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyBadValueSnak extends SnakObject {

	/**
	 * @var mixed
	 */
	protected $rawData;

	/**
	 * @var string
	 */
	protected $errorMessage;

	/**
	 * @var string|null
	 */
	protected $valueType;

	/**
	 * @param EntityId|integer $propertyId
	 * @param string           $errorMessage
	 * @param mixed            $rawData   The data structure that we failed to interpret
	 * @param string|null      $valueType the DataValue type identifier
	 */
	public function __construct( $propertyId, $errorMessage, $rawData = null, $valueType = null ) {
		parent::__construct( $propertyId );

		$this->rawData = $rawData;
		$this->errorMessage = $errorMessage;
		$this->valueType = $valueType;
	}

	/**
	 * Returns the raw data structure of the property's value
	 *
	 * @return mixed
	 */
	public function getRawData() {
		return $this->rawData;
	}

	/**
	 * @return string
	 */
	public function getErrorMessage() {
		return $this->errorMessage;
	}

	/**
	 * Returns the DataValue type identifier, if known.
	 *
	 * @return null|string
	 */
	public function getValueType() {
		return $this->valueType;
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize( array(
			$this->propertyId->getNumericId(),
			$this->errorMessage,
			$this->rawData,
			$this->valueType
		) );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @param string $serialized
	 *
	 * @return PropertyValueSnak
	 */
	public function unserialize( $serialized ) {
		list( $propertyId, $errorMessage, $rawData, $valueType ) = unserialize( $serialized );

		$this->__construct(
			new EntityId( Property::ENTITY_TYPE, $propertyId ),
			$errorMessage,
			$rawData,
			$valueType
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

		$data[] = $this->valueType;
		$data[] = $this->rawData;

		return $data;
	}

	/**
	 * @see Snak::getType
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	public function getType() {
		return 'bad';
	}

}
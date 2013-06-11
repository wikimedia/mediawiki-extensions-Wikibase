<?php

namespace Wikibase\Lib;

use DataTypes\DataTypeFactory;
use DataValues\DataValueFactory;
use DataValues\IllegalValueException;
use DataValues\UnDeserializableValue;
use InvalidArgumentException;
use MWException;
use Wikibase\EntityId;
use Wikibase\Lib\EntityIdParser;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Property;
use Wikibase\Snak;
use Wikibase\SnakFactory;

/**
 * Factory for creating new snaks.
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
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SnakConstructionService {

	/**
	 * @var SnakFactory
	 */
	protected $snakFactory;

	/**
	 * @var PropertyDataTypeLookup
	 */
	protected $dataTypeLookup;

	/**
	 * @var DataTypeFactory
	 */
	protected $dataTypeFactory;

	/**
	 * @var DataValueFactory
	 */
	protected $dataValueFactory;

	/**
	 * @param SnakFactory            $snakFactory
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param DataTypeFactory        $dataTypeFactory
	 * @param DataValueFactory       $dataValueFactory
	 */
	public function __construct(
		SnakFactory $snakFactory,
		PropertyDataTypeLookup $dataTypeLookup,
		DataTypeFactory $dataTypeFactory,
		DataValueFactory $dataValueFactory
	) {
		$this->snakFactory = $snakFactory;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->dataTypeFactory = $dataTypeFactory;
		$this->dataValueFactory = $dataValueFactory;
	}

	/**
	 * Builds and returns a new snak from the provided property, snak type and optional snak value.
	 *
	 * @since 0.3
	 *
	 * @param EntityId    $propertyId
	 * @param string      $snakType
	 * @param mixed       $rawValue
	 *
	 * @return \Wikibase\Snak
	 * @throws InvalidArgumentException
	 * @throws IllegalValueException
	 * @throws PropertyNotFoundException
	 */
	public function newSnak( EntityId $propertyId, $snakType, $rawValue = null ) {
		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new InvalidArgumentException( 'Expected an EntityId of a property' );
		}

		$dataTypeId = $this->dataTypeLookup->getDataTypeIdForProperty( $propertyId );
		$dataType = $this->dataTypeFactory->getType( $dataTypeId );
		$valueType = $dataType->getDataValueType();

		$snakValue = $snakType !== 'value' ? null : $this->dataValueFactory->newDataValue( $valueType, $rawValue );

		$snak = $this->snakFactory->newSnak(
			$propertyId,
			$snakType,
			$snakValue
		);

		return $snak;
	}

}
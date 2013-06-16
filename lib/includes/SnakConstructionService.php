<?php

namespace Wikibase\Lib;

use DataTypes\DataTypeFactory;
use DataValues\IllegalValueException;
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


	public function __construct(
		SnakFactory $snakFactory,
		PropertyDataTypeLookup $dataTypeLookup,
		DataTypeFactory $dataTypeFactory
	) {
		$this->snakFactory = $snakFactory;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->dataTypeFactory = $dataTypeFactory;
	}

	/**
	 * Builds and returns a new snak from the provided property, snak type and optional snak value.
	 *
	 * @since 0.3
	 *
	 * @param EntityId    $propertyId
	 * @param string      $snakType
	 * @param mixed       $snakValue
	 *
	 * @throws IllegalValueException
	 * @throws InvalidArgumentException
	 * @return Snak
	 * @throws MWException
	 * @throws InvalidArgumentException
	 */
	public function newSnak( EntityId $propertyId, $snakType, $snakValue = null ) {
		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new InvalidArgumentException( 'Expected an EntityId of a property' );
		}

		$dataTypeId = $snakValue === null ? null : $this->dataTypeLookup->getDataTypeIdForProperty( $propertyId );
		$dataType = $dataTypeId === null ? null : $this->dataTypeFactory->getType( $dataTypeId );
		$valueType = $dataType === null ? null : $dataType->getDataValueType();

		$snak = $this->snakFactory->newSnak(
			$propertyId,
			$snakType,
			$valueType,
			$snakValue
		);

		return $snak;
	}

}
<?php

namespace Wikibase\Lib;

use DataTypes\DataTypeFactory;
use DataValues\DataValueFactory;
use DataValues\IllegalValueException;
use InvalidArgumentException;
use OutOfBoundsException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\SnakFactory;

/**
 * Factory for creating new snaks.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SnakConstructionService {

	private $snakFactory;
	private $dataTypeLookup;
	private $dataTypeFactory;
	private $dataValueFactory;

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
	 * @param PropertyId $propertyId
	 * @param string $snakType
	 * @param mixed $rawValue
	 *
	 * @return Snak
	 * @throws PropertyNotFoundException from getDataTypeIdForProperty
	 * @throws OutOfBoundsException from getType
	 * @throws InvalidArgumentException from newDataValue and newSnak
	 * @throws IllegalValueException from newDataValue
	 */
	public function newSnak( PropertyId $propertyId, $snakType, $rawValue = null ) {
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

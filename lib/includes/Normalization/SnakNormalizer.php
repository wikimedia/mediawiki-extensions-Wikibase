<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Normalization;

use Psr\Log\LoggerInterface;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class SnakNormalizer {

	/** @var PropertyDataTypeLookup */
	private $dataTypeLookup;

	/** @var LoggerInterface */
	private $logger;

	/** @var callable[] */
	private $normalizerDefinitions;

	/** @var DataValueNormalizer[] */
	private $normalizers = [];

	/**
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * Used to look up property data types for the given snaks.
	 * If the data type cannot be looked up, an info message is logged
	 * and the snak value is only normalized according to its data value type.
	 * @param LoggerInterface $logger For logging that info message.
	 * @param callable[] $normalizerDefinitions
	 * A mapping from data and value types (PT:xxx and VT:xxx) to callables
	 * which return either a single {@link DataValueNormalizer} or a list of them.
	 */
	public function __construct(
		PropertyDataTypeLookup $dataTypeLookup,
		LoggerInterface $logger,
		array $normalizerDefinitions
	) {
		$this->dataTypeLookup = $dataTypeLookup;
		$this->logger = $logger;
		$this->normalizerDefinitions = $normalizerDefinitions;
	}

	public function normalize( Snak $snak ): Snak {
		if ( !( $snak instanceof PropertyValueSnak ) ) {
			return $snak;
		}

		$propertyId = $snak->getPropertyId();
		$value = $snak->getDataValue();
		$normalizer = $this->getNormalizer( $propertyId, $value::getType() );

		return new PropertyValueSnak(
			$propertyId,
			$normalizer->normalize( $value )
		);
	}

	private function getNormalizer( PropertyId $propertyId, string $valueType ) {
		$propertyIdSerialization = $propertyId->getSerialization();
		if ( !array_key_exists( $propertyIdSerialization, $this->normalizers ) ) {
			$this->normalizers[$propertyIdSerialization] = $this->makeNormalizer( $propertyId, $valueType );
		}

		return $this->normalizers[$propertyIdSerialization];
	}

	private function makeNormalizer( PropertyId $propertyId, string $valueType ): DataValueNormalizer {
		$dataTypeNormalizers = [];
		$valueTypeNormalizers = [];

		try {
			$dataType = $this->dataTypeLookup->getDataTypeIdForProperty( $propertyId );
			if ( array_key_exists( "PT:$dataType", $this->normalizerDefinitions ) ) {
				$dataTypeNormalizers = $this->normalizerDefinitions["PT:$dataType"]();
			}
		} catch ( PropertyDataTypeLookupException $e ) {
			$this->logger->info(
				__METHOD__ . ': cannot look up property type of {propertyId}, ' .
				'only normalizing for value type {valueType}',
				[
					'exception' => $e,
					'propertyId' => $propertyId->getSerialization(),
					'valueType' => $valueType,
				]
			);
			$dataType = 'unknown'; // for assertion message below
		}

		if ( array_key_exists( "VT:$valueType", $this->normalizerDefinitions ) ) {
			$valueTypeNormalizers = $this->normalizerDefinitions["VT:$valueType"]();
		}

		$normalizers = array_merge(
			is_array( $dataTypeNormalizers ) ? $dataTypeNormalizers : [ $dataTypeNormalizers ],
			is_array( $valueTypeNormalizers ) ? $valueTypeNormalizers : [ $valueTypeNormalizers ]
		);
		Assert::parameterElementType( DataValueNormalizer::class, $normalizers,
			"data value normalizers for PT:$dataType and VT:$valueType" );
		if ( count( $normalizers ) === 1 ) {
			return array_pop( $normalizers );
		} else {
			return new CompositeDataValueNormalizer( $normalizers );
		}
	}

}

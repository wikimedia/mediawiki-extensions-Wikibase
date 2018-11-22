<?php

namespace Wikibase\Lib;

use Psr\Log\LoggerInterface;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\Lib\Store\PropertyInfoLookup;

/**
 * PropertyDataTypeLookup that uses an PropertyInfoLookup to find
 * a property's data type ID.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class PropertyInfoDataTypeLookup implements PropertyDataTypeLookup {

	/**
	 * @var PropertyDataTypeLookup|null
	 */
	private $fallbackLookup;

	/**
	 * @var PropertyInfoLookup
	 */
	private $infoLookup;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @param PropertyInfoLookup $infoLookup
	 * @param LoggerInterface $logger
	 * @param PropertyDataTypeLookup|null $fallbackLookup
	 */
	public function __construct(
		PropertyInfoLookup $infoLookup,
		LoggerInterface $logger,
		PropertyDataTypeLookup $fallbackLookup = null
	) {
		$this->infoLookup = $infoLookup;
		$this->fallbackLookup = $fallbackLookup;
		$this->logger = $logger;
	}

	/**
	 * @param PropertyId $propertyId
	 *
	 * @return string
	 * @throws PropertyDataTypeLookupException
	 */
	public function getDataTypeIdForProperty( PropertyId $propertyId ) {
		$dataTypeId = null;
		$info = $this->infoLookup->getPropertyInfo( $propertyId );

		if ( $info !== null && isset( $info[PropertyInfoLookup::KEY_DATA_TYPE] ) ) {
			$dataTypeId = $info[PropertyInfoLookup::KEY_DATA_TYPE];
		}

		if ( $dataTypeId === null && $this->fallbackLookup !== null ) {
			$dataTypeId = $this->fallbackLookup->getDataTypeIdForProperty( $propertyId );

			if ( $dataTypeId !== null ) {
				$this->logger->debug(
					'{method}: No property info found for {propertyId}, but property ID could be retrieved from fallback store!',
					[
						'method' => __METHOD__,
						'propertyId' => $propertyId,
					]
				);
				//TODO: Automatically update the info store?
				//TODO: Suggest to run rebuildPropertyInfo.php
			}
		}

		if ( $dataTypeId === null ) {
			throw new PropertyDataTypeLookupException( $propertyId );
		}

		return $dataTypeId;
	}

}

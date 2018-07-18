<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;

class AnotherEntityInfoBuilder implements EntityInfoBuilder {

	/**
	 * @var LabelDescriptionLookupForBatch
	 */
	private $batchTermLookup;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @param EntityId[] $entityIds
	 * @param string[] $languageCodes
	 *
	 * @return EntityInfo
	 */
	public function collectEntityInfo( array $entityIds, array $languageCodes ) {
		$labelsPerEntity = $this->batchTermLookup->getLabels( $entityIds, $languageCodes );
		$descriptionsPerEntity = $this->batchTermLookup->getDescriptions( $entityIds, $languageCodes );

		$propertyDataTypes = $this->getDataTypes( $entityIds );
		$redirectTargets = $this->getRedirects( $entityIds );

		return $this->buildEntityInfo( $labelsPerEntity, $descriptionsPerEntity, $propertyDataTypes, $redirectTargets );
	}

	/**
	 * TODO: should do a batch lookup for all properties at one go?
	 *
	 * @param EntityId[] $entityIds
	 *
	 * @return string[]
	 */
	private function getDataTypes( array $entityIds ) {
		$dataTypes = [];

		foreach ( $entityIds as $id ) {
			if ( $id->getEntityType() !== Property::ENTITY_TYPE ) {
				continue;
			}

			$dataTypes[$id->getSerialization()] = $this->dataTypeLookup->getDataTypeIdForProperty( $id );
		}

		return $dataTypes;
	}

	private function getRedirects( $entityIds ) {
		// TODO: is there any decent way to do it? SqlEntityInfoBuilder queries page and redirect tables
		// TODO: is this even needed though?
	}
}
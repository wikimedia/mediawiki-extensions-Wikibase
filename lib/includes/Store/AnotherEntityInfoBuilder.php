<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
class AnotherEntityInfoBuilder implements EntityInfoBuilder {

	/**
	 * @var LabelDescriptionLookupForBatch
	 */
	private $batchTermLookup;

	public function __construct( LabelDescriptionLookupForBatch $batchTermLookup ) {
		$this->batchTermLookup = $batchTermLookup;
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param string[] $languageCodes
	 *
	 * @return EntityInfo
	 */
	public function collectEntityInfo( array $entityIds, array $languageCodes ) {
		$labelsPerEntity = $this->batchTermLookup->getLabels( $entityIds, $languageCodes );
		$descriptionsPerEntity = $this->batchTermLookup->getDescriptions( $entityIds, $languageCodes );

		return $this->buildEntityInfo( $entityIds, $labelsPerEntity, $descriptionsPerEntity );
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param array $labelsPerEntity
	 * @param array $descriptionsPerEntity
	 * @param array $redirectTargets
	 *
	 * @return EntityInfo
	 */
	private function buildEntityInfo( array $entityIds, $labelsPerEntity, $descriptionsPerEntity ) {
		$info = [];
		foreach ( $entityIds as $entityId ) {
			$id = $entityId->getSerialization();

			// TODO: Hacky!!!!11
			// If no labels nor description, assume the entity does not exist, and skip it from results
			if ( !array_key_exists( $id, $labelsPerEntity ) && !array_key_exists( $id, $descriptionsPerEntity ) ) {
				continue;
			}

			$labels = [];
			// TODO :Extract
			if ( array_key_exists( $id, $labelsPerEntity ) ) {
				foreach ( $labelsPerEntity[$id] as $language => $value ) {
					$labels[$language] = [
						'language' => $language,
						'value' => $value,
					];
				}
			}
			$descriptions = [];
			// TODO :Extract
			if ( array_key_exists( $id, $descriptionsPerEntity ) ) {
				foreach ( $descriptionsPerEntity[$id] as $language => $value ) {
					$descriptions[$language] = [
						'language' => $language,
						'value' => $value,
					];
				}
			}

			$info[$id] = [
				'labels' => $labels,
				'descriptions' => $descriptions,
			];
		}

		return new EntityInfo( $info );
	}

}

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

		$redirectTargets = $this->getRedirects( $entityIds );

		return $this->buildEntityInfo( $entityIds, $labelsPerEntity, $descriptionsPerEntity, $redirectTargets );
	}

	private function getRedirects( $entityIds ) {
		// TODO: is there any decent way to do it? SqlEntityInfoBuilder queries page and redirect tables
		// TODO: is this even needed though?
		return [];
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param array $labelsPerEntity
	 * @param array $descriptionsPerEntity
	 * @param array $redirectTargets
	 *
	 * @return EntityInfo
	 */
	private function buildEntityInfo( array $entityIds, $labelsPerEntity, $descriptionsPerEntity, $redirectTargets ) {
		$info = [];
		foreach ( $entityIds as $entityId ) {
			$id = $entityId->getSerialization();
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

			$info[$id] = [
				'id' => $id,
				'type' => $entityId->getEntityType(),
				'labels' => $labels,
				'descriptions' => [], // TODO
			];
		}

		return new EntityInfo( $info );
	}

}

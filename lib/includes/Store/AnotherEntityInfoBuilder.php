<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

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

		return $this->buildEntityInfo( $labelsPerEntity, $descriptionsPerEntity, $redirectTargets );
	}

	private function getRedirects( $entityIds ) {
		// TODO: is there any decent way to do it? SqlEntityInfoBuilder queries page and redirect tables
		// TODO: is this even needed though?
		return [];
	}

	private function buildEntityInfo( $labelsPerEntity, $descriptionsPerEntity, $redirectTargets ) {
		return new EntityInfo( [] );
	}

}
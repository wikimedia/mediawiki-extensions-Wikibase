<?php

namespace Wikibase\Client\Usage;

/**
 * This class de-duplicates entity usages for performance and storage reasons
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani
 */
class UsageDeduplicator {

	/**
	 * @param EntityUsage[] $usages
	 * @return EntityUsage[]
	 */
	public function deduplicate( array $usages ) {
		$structuredUsages = $this->structureUsages( $usages );

		foreach ( $structuredUsages as $entityId => $usages ) {
			$structuredUsages[$entityId] = $this->deduplicateUsagesPerEntity( $usages );
		}

		// Flatten the structured array
		$return = [];
		array_walk_recursive(
			$structuredUsages,
			function( $a ) use ( &$return ) {
				/* @var EntityUsage $a */
				$return[$a->getIdentityString()] = $a;
			}
		);
		return $return;
	}

	/**
	 * @param EntityUsage[] $usages
	 * @return array[]
	 */
	private function structureUsages( array $usages ) {
		$structuredUsages = [];
		foreach ( $usages as $usage ) {
			$entityId = $usage->getEntityId();
			$structuredUsages[$entityId->getSerialization()][] = $usage;
		}

		return array_map( [ $this, 'structureUsagesPerEntity' ], $structuredUsages );
	}

	/**
	 * @param EntityUsage[] $usages
	 * @return array[]
	 */
	private function structureUsagesPerEntity( array $usages ) {
		$structuredUsages = [
			EntityUsage::DESCRIPTION_USAGE => [],
			EntityUsage::LABEL_USAGE => [],
		];
		foreach ( $usages as $usage ) {
			$aspect = $usage->getAspect();
			$structuredUsages[$aspect][] = $usage;
		}

		return $structuredUsages;
	}

	/**
	 * @param EntityUsage[] $usages
	 * @return EntityUsage[]
	 */
	private function deduplicateUsagesPerEntity( array $usages ) {
		$usages[EntityUsage::DESCRIPTION_USAGE] = $this->deduplicatePerType(
			$usages[EntityUsage::DESCRIPTION_USAGE]
		);
		$usages[EntityUsage::LABEL_USAGE] = $this->deduplicatePerType(
			$usages[EntityUsage::LABEL_USAGE]
		);
		return $usages;
	}

	/**
	 * @param EntityUsage[] $usages
	 * @return EntityUsage[]
	 */
	private function deduplicatePerType( array $usages ) {
		foreach ( $usages as $usage ) {
			if ( $usage->getModifier() === null ) {
				return [ $usage ];
			}
		}

		return $usages;
	}

}

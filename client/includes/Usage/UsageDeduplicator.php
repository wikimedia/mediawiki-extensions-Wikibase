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
	 */
	public function deduplicate( array $usages ) {
		$structuredUsages = $this->structureUsages( $usages );

		foreach ( $structuredUsages as $entityId => $usages ) {
			$structuredUsages[$entityId] = $this->deduplicateUsagesPerEntity( $usages );
		}

		// Flatten the structured array
		$return = [];
		array_walk_recursive( $structuredUsages,
			function( $a ) use ( &$return ) {
				$return[] = $a;
			}
		);
		return $return;
	}

	/**
	 * @param EntityUsage[] $usages
	 */
	private function structureUsages( array $usages ) {
		$structuredUsages = [];
		foreach ( $usages as $usage ) {
			$entityId = $usage->getEntityId();
			if ( isset( $structuredUsages[$entityId->getSerialization()] ) ) {
				$structuredUsages[$entityId->getSerialization()][] = $usage;
			} else {
				$structuredUsages[$entityId->getSerialization()] = [ $usage ];
			}
		}

		$reallyStructuredUsages = [];
		foreach ( $structuredUsages as $entityId => $usages ) {
			$reallyStructuredUsages[$entityId] = $this->structureUsagesPerEntity( $usages );
		}

		return $reallyStructuredUsages;
	}

	private function structureUsagesPerEntity( array $usages ) {
		$structuredUsages = [
			EntityUsage::DESCRIPTION_USAGE => [],
			EntityUsage::LABEL_USAGE => [],
		];
		foreach ( $usages as $usage ) {
			$aspect = $usage->getAspect();
			if ( isset( $structuredUsages[$aspect] ) ) {
				$structuredUsages[$aspect][] = $usage;
			} else {
				$structuredUsages[$aspect] = [ $usage ];
			}
		}

		return $structuredUsages;
	}

	private function deduplicateUsagesPerEntity( $usages ) {
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

<?php

namespace Wikibase\Client\Usage;

use Wikimedia\Assert\Assert;

/**
 * This class de-duplicates entity usages for performance and storage reasons
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani
 */
class UsageDeduplicator {

	/**
	 * @var int[]
	 */
	private $usageModifierLimits;

	/**
	 * @param int[] $usageModifierLimits associative array mapping usage type to the limit
	 */
	public function __construct( array $usageModifierLimits ) {
		Assert::parameterElementType( 'integer', $usageModifierLimits, '$usageModifierLimits' );

		$this->usageModifierLimits = $usageModifierLimits;
	}

	/**
	 * @param EntityUsage[] $usages
	 *
	 * @return EntityUsage[]
	 */
	public function deduplicate( array $usages ) {
		$structuredUsages = $this->structureUsages( $usages );
		$structuredUsages = $this->deduplicateStructuredUsages( $structuredUsages );
		return $this->flattenStructuredUsages( $structuredUsages );
	}

	/**
	 * @param EntityUsage[] $usages
	 *
	 * @return array[][] three-dimensional array of
	 *  [ $entityId => [ $aspectKey => [ EntityUsage $usage, … ], … ], … ]
	 */
	private function structureUsages( array $usages ) {
		$structuredUsages = [];

		foreach ( $usages as $usage ) {
			$entityId = $usage->getEntityId()->getSerialization();
			$aspect = $usage->getAspect();
			$structuredUsages[$entityId][$aspect][] = $usage;
		}

		return $structuredUsages;
	}

	/**
	 * @param array[][] $structuredUsages
	 *
	 * @return array[]
	 */
	private function deduplicateStructuredUsages( array $structuredUsages ) {
		foreach ( $structuredUsages as &$usagesPerEntity ) {
			foreach ( $usagesPerEntity as $aspect => &$usagesPerAspect ) {
				$this->limitPerAspect( $aspect, $usagesPerAspect );
				$this->deduplicatePerAspect( $usagesPerAspect );
			}
		}

		return $structuredUsages;
	}

	/**
	 * @param string $aspect
	 * @param EntityUsage[] &$usages
	 */
	private function limitPerAspect( $aspect, array &$usages ) {
		if ( !isset( $this->usageModifierLimits[$aspect] ) ) {
			return;
		}

		if ( count( $usages ) > $this->usageModifierLimits[$aspect] ) {
			$usages = [
				new EntityUsage(
					$usages[0]->getEntityId(),
					$usages[0]->getAspect()
					// Throw away modifier
				),
			];
		}
	}

	/**
	 * @param EntityUsage[] &$usages
	 */
	private function deduplicatePerAspect( array &$usages ) {
		foreach ( $usages as $usage ) {
			if ( $usage->getModifier() === null ) {
				// This intentionally flattens the array to a single value
				$usages = $usage;
				return;
			}
		}
	}

	/**
	 * @param array[] $structuredUsages
	 *
	 * @return EntityUsage[]
	 */
	private function flattenStructuredUsages( array $structuredUsages ) {
		$usages = [];

		array_walk_recursive(
			$structuredUsages,
			function ( EntityUsage $usage ) use ( &$usages ) {
				$usages[$usage->getIdentityString()] = $usage;
			}
		);

		return $usages;
	}

}

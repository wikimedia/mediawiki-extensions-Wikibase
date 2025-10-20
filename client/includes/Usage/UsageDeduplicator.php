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
			$containsQualOrReference = array_filter(
				$usagesPerEntity,
				function ( $value, $key ) {
					return $key === EntityUsage::STATEMENT_WITH_QUAL_OR_REF_USAGE;
				},
				ARRAY_FILTER_USE_BOTH
			) !== [];
			if ( $containsQualOrReference ) {
				$deduplicatedStatementUsage = $this->deduplicateStatementUsages(
					$usagesPerEntity[EntityUsage::STATEMENT_USAGE],
					$usagesPerEntity[EntityUsage::STATEMENT_WITH_QUAL_OR_REF_USAGE]
				);
				$usagesPerEntity[EntityUsage::STATEMENT_USAGE] = $deduplicatedStatementUsage[EntityUsage::STATEMENT_USAGE];
				$usagesPerEntity[EntityUsage::STATEMENT_WITH_QUAL_OR_REF_USAGE] =
					$deduplicatedStatementUsage[EntityUsage::STATEMENT_WITH_QUAL_OR_REF_USAGE];
			}

			foreach ( $usagesPerEntity as $aspect => &$usagesPerAspect ) {
				$this->limitPerAspect( $aspect, $usagesPerAspect );
				$this->deduplicatePerAspect( $usagesPerAspect );
			}
		}

		return $structuredUsages;
	}

	/**
	 * @param EntityUsage[] $statementUsages
	 * @param EntityUsage[] $statementWithQualOrRefUsages
	 */
	private function deduplicateStatementUsages( array $statementUsages, array $statementWithQualOrRefUsages ): array {
		foreach ( $statementWithQualOrRefUsages as $statementWithQualOrRefUsage ) {
			if ( $statementWithQualOrRefUsage->getModifier() === null ) {
				$statementUsages = [];
			} else {
					// If CQR does have a modifier, remove C usages with that modifier
				$modifier = $statementWithQualOrRefUsage->getModifier();
				$statementUsages = array_filter( $statementUsages, function ( $usage ) use ( $modifier ) {
					return $usage->getModifier() !== $modifier;
				} );
			}
		}
		// If the combined CQR and C usages with independent modifiers > 33, throw away the QR modifier and remove C usages
		$combinedStatementUsages = [ ...$statementUsages, ...$statementWithQualOrRefUsages ];
		$statementUsageLimit = $this->usageModifierLimits[EntityUsage::STATEMENT_USAGE];
		if ( $statementUsageLimit !== null ) {
			if ( count( $combinedStatementUsages ) > $statementUsageLimit ) {
				$statementUsages = [];
				$statementWithQualOrRefUsages = [ new EntityUsage(
					$statementWithQualOrRefUsages[0]->getEntityId(),
					EntityUsage::STATEMENT_WITH_QUAL_OR_REF_USAGE
				// Throw away modifier
				) ];
			}
		}
		return [ EntityUsage::STATEMENT_USAGE => $statementUsages,
			EntityUsage::STATEMENT_WITH_QUAL_OR_REF_USAGE => $statementWithQualOrRefUsages,
		];
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

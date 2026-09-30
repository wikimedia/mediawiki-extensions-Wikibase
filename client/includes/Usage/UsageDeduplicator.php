<?php

namespace Wikibase\Client\Usage;

use Psr\Log\LoggerInterface;
use Wikibase\DataModel\Entity\EntityId;
use Wikimedia\Assert\Assert;
use Wikimedia\Stats\StatsFactory;

/**
 * This class de-duplicates entity usages for performance and storage reasons
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani
 */
class UsageDeduplicator {
	private const array HISTOGRAM_BUCKETS = [ 1, 10, 25, 40, 70, 100, 150, 300, 500, 800 ];

	/**
	 * @param int[] $usageModifierLimits associative array mapping a usage type to the limit
	 * @param LoggerInterface $logger
	 * @param StatsFactory $stats
	 * @param string $wiki
	 */
	public function __construct(
		private readonly array $usageModifierLimits, private readonly LoggerInterface $logger,
		private readonly StatsFactory $stats, private readonly string $wiki
	) {
		Assert::parameterElementType( 'integer', $usageModifierLimits, '$usageModifierLimits' );
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
			$containsQualOrReference = array_key_exists(
				EntityUsage::STATEMENT_WITH_QUAL_OR_REF_USAGE,
				$usagesPerEntity
			);
			if ( $containsQualOrReference ) {
				if ( !isset( $usagesPerEntity[EntityUsage::STATEMENT_USAGE] ) ) {
					$this->logger->warning(
						'UsageDeduplicator: Statement usage (C) is missing while CQR usage is present.',
						[ 'usagesPerEntity' => $usagesPerEntity ]
					);
					$usagesPerEntity[EntityUsage::STATEMENT_USAGE] = [];
				}
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
	 * @return array
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
		$statementUsageLimit = $this->usageModifierLimits[EntityUsage::STATEMENT_USAGE];
		if ( $statementUsageLimit !== null ) {
			// If the combined CQR and C usages with independent modifiers is more
			// than the limit, then throw away the QR modifier and remove C usages
			$combinedStatementUsages = [ ...$statementUsages, ...$statementWithQualOrRefUsages ];
			$usageCount = count( $combinedStatementUsages );
			$exceedsLimit = $usageCount > $statementUsageLimit;
			$aspect = EntityUsage::STATEMENT_WITH_QUAL_OR_REF_USAGE;
			$this->collectBundleUsagesLimitStats(
				$usageCount, $aspect, $exceedsLimit, $combinedStatementUsages[0]->getEntityId()
			);
			if ( $exceedsLimit ) {
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
	 * @param int $count
	 * @param string $aspect
	 * @param bool $exceedsLimit
	 * @param EntityId $entityId
	 * @return void
	 */
	private function collectBundleUsagesLimitStats(
		int $count, string $aspect, bool $exceedsLimit, EntityId $entityId
	): void {
		$this->stats->getHistogram(
			'WikibaseClient_UsageDeduplicator_bundled_entity_usages',
			self::HISTOGRAM_BUCKETS
		)
			->setLabel( 'wiki', $this->wiki )
			->setLabel( 'exceeds_limit', $exceedsLimit ? 'true' : 'false' )
			->setLabel( 'aspect', $aspect )
			->observe( $count );

		if ( $exceedsLimit ) {
			$this->logger->info(
				"Exceeded entity usage aspect bundling for aspect {aspect}",
				[
					'count' => $count,
					'aspect' => $aspect,
					'entity_id' => $entityId->getSerialization(),
					'wiki' => $this->wiki,
				]
			);
		}
	}

	/**
	 * @param string $aspect
	 * @param EntityUsage[] &$usages
	 */
	private function limitPerAspect( string $aspect, array &$usages ) {
		if ( !isset( $this->usageModifierLimits[$aspect] ) ) {
			return;
		}
		$usageCount = count( $usages );
		if ( $usageCount === 0 ) {
			return;
		}

		$exceedsLimit = $usageCount > $this->usageModifierLimits[$aspect];
		$this->collectBundleUsagesLimitStats(
			$usageCount, $aspect, $exceedsLimit, $usages[0]->getEntityId()
		);
		if ( $exceedsLimit ) {
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

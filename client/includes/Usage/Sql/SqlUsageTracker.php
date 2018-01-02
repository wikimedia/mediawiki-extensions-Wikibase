<?php

namespace Wikibase\Client\Usage\Sql;

use ArrayIterator;
use InvalidArgumentException;
use Traversable;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\DBError;
use Wikimedia\Rdbms\SessionConsistentConnectionManager;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Client\Usage\UsageTracker;
use Wikibase\Client\Usage\UsageTrackerException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * An SQL based usage tracker implementation.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class SqlUsageTracker implements UsageTracker, UsageLookup {

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var SessionConsistentConnectionManager
	 */
	private $connectionManager;

	/**
	 * Usage aspects in this array won't be persisted. If string keys are used, this
	 * is treated as [ 'usage-aspect-to-replace' => 'replacement' ].
	 *
	 * @var string[]
	 */
	private $disabledUsageAspects;

	/**
	 * @param EntityIdParser $idParser
	 * @param SessionConsistentConnectionManager $connectionManager
	 * @param string[] $disabledUsageAspects
	 */
	public function __construct(
		EntityIdParser $idParser,
		SessionConsistentConnectionManager $connectionManager,
		array $disabledUsageAspects
	) {
		$this->idParser = $idParser;
		$this->connectionManager = $connectionManager;
		$this->disabledUsageAspects = $disabledUsageAspects;
	}

	/**
	 * @param IDatabase $db
	 *
	 * @return EntityUsageTable
	 */
	private function newUsageTable( IDatabase $db ) {
		return new EntityUsageTable( $this->idParser, $db );
	}

	/**
	 * Re-indexes the given list of EntityUsages so that each EntityUsage can be found by using its
	 * string representation as a key.
	 *
	 * @param EntityUsage[] $usages
	 *
	 * @throws InvalidArgumentException
	 * @return EntityUsage[]
	 */
	private function reindexEntityUsages( array $usages ) {
		$reindexed = [];

		foreach ( $usages as $usage ) {
			if ( !( $usage instanceof EntityUsage ) ) {
				throw new InvalidArgumentException( '$usages must contain EntityUsage objects.' );
			}

			$key = $usage->getIdentityString();
			$reindexed[$key] = $usage;
		}

		return $reindexed;
	}

	/**
	 * @param EntityUsage[] $usages
	 *
	 * @throws InvalidArgumentException
	 * @return EntityUsage[]
	 */
	private function handleBlacklistedUsages( array $usages ) {
		$newUsages = [];

		foreach ( $usages as $usage ) {
			if ( !( $usage instanceof EntityUsage ) ) {
				throw new InvalidArgumentException( '$usages must contain EntityUsage objects.' );
			}

			// Disabled usage with replacement
			if ( isset( $this->disabledUsageAspects[$usage->getAspect()] ) ) {
				$newUsages[] = new EntityUsage( $usage->getEntityId(), $this->disabledUsageAspects[$usage->getAspect()] );
				continue;
			}

			// Disabled usage aspects without replacement (integer key, no replace from -> to map)
			if ( is_int( array_search( $usage->getAspect(), $this->disabledUsageAspects ) ) ) {
				continue;
			}

			$newUsages[] = $usage;
		}

		return $newUsages;
	}

	/**
	 * @see UsageTracker::addUsedEntities
	 *
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @throws InvalidArgumentException
	 * @throws UsageTrackerException
	 */
	public function addUsedEntities( $pageId, array $usages ) {
		if ( !is_int( $pageId ) ) {
			throw new InvalidArgumentException( '$pageId must be an int.' );
		}

		$usages = $this->handleBlacklistedUsages( $usages );
		if ( empty( $usages ) ) {
			return;
		}

		// NOTE: while logically we'd like the below to be atomic, we don't wrap it in a
		// transaction to prevent long lock retention during big updates.
		$db = $this->connectionManager->getWriteConnection();

		try {
			$usageTable = $this->newUsageTable( $db );
			// queryUsages guarantees this to be identity string => EntityUsage
			$oldUsages = $usageTable->queryUsages( $pageId );

			$newUsages = $this->reindexEntityUsages( $usages );

			$added = array_diff_key( $newUsages, $oldUsages );

			// Actually add the new entries
			$usageTable->addUsages( $pageId, $added );
		} catch ( DBError $ex ) {
			throw new UsageTrackerException( $ex->getMessage(), $ex->getCode(), $ex );
		} finally {
			$this->connectionManager->releaseConnection( $db );
		}
	}

	/**
	 * @see UsageTracker::replaceUsedEntities
	 *
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @return EntityUsage[] Usages that have been removed
	 *
	 * @throws InvalidArgumentException
	 * @throws UsageTrackerException
	 */
	public function replaceUsedEntities( $pageId, array $usages ) {
		if ( !is_int( $pageId ) ) {
			throw new InvalidArgumentException( '$pageId must be an int.' );
		}

		// NOTE: while logically we'd like the below to be atomic, we don't wrap it in a
		// transaction to prevent long lock retention during big updates.
		$db = $this->connectionManager->getWriteConnection();

		try {
			$usageTable = $this->newUsageTable( $db );
			// queryUsages guarantees this to be identity string => EntityUsage
			$oldUsages = $usageTable->queryUsages( $pageId );

			$usages = $this->handleBlacklistedUsages( $usages );
			$newUsages = $this->reindexEntityUsages( $usages );

			$removed = array_diff_key( $oldUsages, $newUsages );
			$added = array_diff_key( $newUsages, $oldUsages );

			$usageTable->removeUsages( $pageId, $removed );
			$usageTable->addUsages( $pageId, $added );

			return $removed;
		} catch ( DBError $ex ) {
			throw new UsageTrackerException( $ex->getMessage(), $ex->getCode(), $ex );
		} finally {
			$this->connectionManager->releaseConnection( $db );
		}
	}

	/**
	 * @see UsageTracker::pruneUsages
	 *
	 * @param int $pageId
	 *
	 * @return EntityUsage[]
	 * @throws UsageTrackerException
	 */
	public function pruneUsages( $pageId ) {
		// NOTE: while logically we'd like the below to be atomic, we don't wrap it in a
		// transaction to prevent long lock retention during big updates.
		$db = $this->connectionManager->getWriteConnection();

		try {
			$usageTable = $this->newUsageTable( $db );
			$pruned = $usageTable->pruneUsages( $pageId );

			return $pruned;
		} catch ( DBError $ex ) {
			throw new UsageTrackerException( $ex->getMessage(), $ex->getCode(), $ex );
		} finally {
			$this->connectionManager->releaseConnection( $db );
		}
	}

	/**
	 * @see UsageLookup::getUsagesForPage
	 *
	 * @param int $pageId
	 *
	 * @return EntityUsage[]
	 * @throws UsageTrackerException
	 */
	public function getUsagesForPage( $pageId ) {
		$db = $this->connectionManager->getReadConnection();

		$usageTable = $this->newUsageTable( $db );
		$usages = $usageTable->queryUsages( $pageId );

		$this->connectionManager->releaseConnection( $db );

		return $usages;
	}

	/**
	 * @see UsageLookup::getPagesUsing
	 *
	 * @param EntityId[] $entityIds
	 * @param string[] $aspects
	 *
	 * @return Traversable A traversable over PageEntityUsages grouped by page.
	 * @throws UsageTrackerException
	 */
	public function getPagesUsing( array $entityIds, array $aspects = [] ) {
		if ( empty( $entityIds ) ) {
			return new ArrayIterator();
		}

		$db = $this->connectionManager->getReadConnection();

		$usageTable = $this->newUsageTable( $db );
		$pages = $usageTable->getPagesUsing( $entityIds, $aspects );

		$this->connectionManager->releaseConnection( $db );

		return $pages;
	}

	/**
	 * @see UsageLookup::getUnusedEntities
	 *
	 * @param EntityId[] $entityIds
	 *
	 * @return EntityId[]
	 * @throws UsageTrackerException
	 */
	public function getUnusedEntities( array $entityIds ) {
		if ( empty( $entityIds ) ) {
			return [];
		}

		$db = $this->connectionManager->getReadConnection();

		$usageTable = $this->newUsageTable( $db );
		$unused = $usageTable->getUnusedEntities( $entityIds );

		$this->connectionManager->releaseConnection( $db );

		return $unused;
	}

}

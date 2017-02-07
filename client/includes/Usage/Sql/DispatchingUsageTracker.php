<?php

namespace Wikibase\Client\Usage\Sql;

use ArrayIterator;
use Database;
use DBError;
use Exception;
use InvalidArgumentException;
use Traversable;
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
class DispatchingUsageTracker implements UsageTracker, UsageLookup {

	/**
	 * @var <Update>
	 */
	private $sqlStatementUsageTracker;

	/**
	 * @var <UPDate>
	 */
	private $sqlEntityUsageTracker;


	/**
	 * @param EntityIdParser $idParser
	 * @param SessionConsistentConnectionManager $connectionManager
	 */
	public function __construct( SqlUsageTracker $statementUsageTracker, SqlUsageTracker $entityUsageTracker ) {
		$this->$sqlStatementUsageTracker = $statementUsageTracker;
		$this->$sqlEntityUsageTracker = $entityUsageTracker;
	}


	/**
	 * @see UsageTracker::addUsedEntities
	 *
	 * @param int $pageId
	 * @param EntityDataUsage[] $usages
	 *
	 */
	public function addUsedEntities( $pageId, array $usages ) {
		//if usages = empty, just return
		$entityUsages = [];
		$statementUsages = [];
		foreach ( $usage in $usages ){
			if ( $usage instanceof EntityUsage ){
				$entityUsages[] = $usage;
			}
			else {
				$statementUsages[] = $usage;
			}
		}
		$this->sqlEntityUsageTracker->addUsedEntities( $pageId, $entityUsages );
		$this->sqlStatementUsageTracker->addUsedEntities( $pageId, $statementUsages );
	}

	/**
	 * @see UsageTracker::replaceUsedEntities
	 *
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @return EntityDataUsage[] Usages that have been removed
	 *
	 */
	public function replaceUsedEntities( $pageId, array $usages ) {
		$entityUsages = [];
		$statementUsages = [];
		foreach ( $usage in $usages ){
			if ( $usage instanceof EntityUsage ){
				$entityUsages[] = $usage;
			}
			else {
				$statementUsages[] = $usage;
			}
		}
		// Should the following statments be in the if statement above?
		$removedEntityUsages = $this->sqlEntityUsageTracker->replaceUsedEntities( $pageId, $entityUsages );
		$removedStatementUsages = $this->sqlStatementUsageTracker->replaceUsedEntities( $pageId, $statementUsages );
		return array_merge($removedEntityUsages, $removedStatementUsages);
	}

	/**
	 * @see UsageTracker::pruneUsages
	 *
	 * @param int $pageId
	 *
	 * @return EntityDataUsage[]
	 */
	public function pruneUsages( $pageId ) {
		$prunedEntityUsages = $this->sqlEntityUsageTracker->pruneUsages( $pageId);
		$prunedStatementUsages = $this->sqlStatementUsageTracker->pruneUsages( $pageId );
		return array_merge($prunedEntityUsages, $prunedStatementUsages);
	}
}

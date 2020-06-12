<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @license GPL-2.0-or-later
 */
class DatabaseUsageCheckingTermStoreCleaner implements TermStoreCleaner {

	/**
	 * @var DatabaseInnerTermStoreCleaner
	 */
	private $innerCleaner;
	/**
	 * @var ILoadBalancer
	 */
	private $loadBalancer;

	public function __construct( ILoadBalancer $loadbalancer, DatabaseInnerTermStoreCleaner $innerCleaner ) {
		$this->loadBalancer = $loadbalancer;
		$this->innerCleaner = $innerCleaner;
	}

	/**
	 * Checks the provided TermInLangIds for existence and usage in either
	 * on both Items and Properties.
	 *
	 * Those that do actually exist and are unused are passed to an inner cleaner.
	 *
	 * These steps are all wrapped in a transaction.
	 *
	 * @param array $termInLangIds
	 */
	public function cleanTermInLangIds( array $termInLangIds ): void {
		$dbw = $this->loadBalancer->getConnection( ILoadBalancer::DB_MASTER );
		$dbr = $this->loadBalancer->getConnection( ILoadBalancer::DB_REPLICA );

		$dbw->startAtomic( __METHOD__ );
		$unusedTermInLangIds = $this->findActuallyUnusedTermInLangIds( $dbw, $termInLangIds );
		$this->innerCleaner->cleanTermInLangIds( $dbw, $dbr, $unusedTermInLangIds );
		$dbw->endAtomic( __METHOD__ );
	}

	/**
	 * Of the given term in lang IDs, find those that are not used by any other items or properties.
	 *
	 * Currently, this does not account for term in lang IDs that may be used anywhere else,
	 * e.g. by other entity types; anyone who uses term in lang IDs elsewhere runs the risk
	 * of those terms being deleted at any time. This may be improved in the future.
	 *
	 * 1) Iterate through the IDs that we have been given and determine if they
	 * appear to be used or not in either the property or item term tables.
	 * 2) Select FOR UPDATE the rows in the wbt_property_terms and wbt_item_terms
	 * tables so they lock and nothing will happen to them.
	 *
	 * An alternative to this would be immediately lock all $termInLangIds, but that would
	 * lead to deadlocks. see T234948
	 *
	 * @param IDatabase $dbw
	 * @param int[] $termInLangIds (wbtl_id)
	 * @return int[] wbtl_ids to be cleaned
	 * @todo This should be factored out of this Cleaner into its own class
	 *
	 */
	private function findActuallyUnusedTermInLangIds( IDatabase $dbw, array $termInLangIds ) {

		$unusedTermInLangIds = [];
		foreach ( $termInLangIds as $termInLangId ) {
			// Note: Not batching here is intentional to avoid deadlocks (see method comment)
			$usedInProperties = $dbw->selectField(
				'wbt_property_terms',
				'wbpt_term_in_lang_id',
				[ 'wbpt_term_in_lang_id' => $termInLangId ],
				__METHOD__
			);
			$usedInItems = $dbw->selectField(
				'wbt_item_terms',
				'wbit_term_in_lang_id',
				[ 'wbit_term_in_lang_id' => $termInLangId ],
				__METHOD__
			);

			if ( $usedInProperties === false && $usedInItems === false ) {
				$unusedTermInLangIds[] = $termInLangId;
			}
		}
		if ( $unusedTermInLangIds === [] ) {
			return [];
		}

		$termInLangIdsUsedInPropertiesSinceLastLoopRan = $dbw->selectFieldValues(
			'wbt_property_terms',
			'wbpt_term_in_lang_id',
			[ 'wbpt_term_in_lang_id' => $unusedTermInLangIds ],
			__METHOD__,
			[
				'FOR UPDATE'
			]
		);
		$termInLangIdsUsedInItemsSinceLastLoopRan = $dbw->selectFieldValues(
			'wbt_item_terms',
			'wbit_term_in_lang_id',
			[ 'wbit_term_in_lang_id' => $unusedTermInLangIds ],
			__METHOD__,
			[
				'FOR UPDATE'
			]
		);

		$finalUnusedTermInLangIds = array_diff(
			$unusedTermInLangIds,
			$termInLangIdsUsedInPropertiesSinceLastLoopRan,
			$termInLangIdsUsedInItemsSinceLastLoopRan
		);

		return $finalUnusedTermInLangIds;
	}

}

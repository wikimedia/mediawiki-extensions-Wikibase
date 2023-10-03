<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Store\Sql\Terms;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wikibase\Lib\Store\Sql\Terms\Util\StatsdMonitoring;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IReadableDatabase;

/**
 * Cleans up the normalized term store after some terms are no longer needed.
 * Unused wbt_term_in_lang, wbt_text_in_lang and wbt_text rows are automatically removed.
 * Unused type rows are never cleaned up.
 *
 * @see @ref docs_storage_terms
 * @license GPL-2.0-or-later
 */
class DatabaseInnerTermStoreCleaner {

	use StatsdMonitoring;

	/** @var IReadableDatabase a connection to DB_REPLICA. Note only set on cleanTermInLangIds */
	private ?IReadableDatabase $dbr = null;

	/** @var IDatabase a connection to DB_PRIMARY. Note only set on cleanTermInLangIds */
	private ?IDatabase $dbw = null;

	private LoggerInterface $logger;

	public function __construct(
		LoggerInterface $logger = null
	) {
		$this->logger = $logger ?: new NullLogger();
	}

	/**
	 * Delete the specified wbt_term_in_lang rows from the database,
	 * as well as any wbt_text_in_lang and wbt_text rows that are now unused.
	 *
	 * It is the caller’s responsibility ensure
	 * that the wbt_term_in_lang rows are no longer referenced anywhere;
	 * callers will most likely want to wrap this call in a transaction for that.
	 * On the other hand, this class takes care that wbt_text_in_lang and text rows
	 * used by other wbt_term_in_lang rows are not removed.
	 *
	 * @param IDatabase $dbw
	 * @param IReadableDatabase $dbr
	 * @param int[] $termInLangIds (wbtl_id)
	 */
	public function cleanTermInLangIds( IDatabase $dbw, IReadableDatabase $dbr, array $termInLangIds ): void {
		if ( $termInLangIds === [] ) {
			return;
		}

		$this->dbw = $dbw;
		$this->dbr = $dbr;
		$this->cleanTermInLangIdsInner( $termInLangIds );
	}

	/**
	 * Delete the specified term_in_lang rows from the database,
	 * as well as any text_in_lang and text rows that are now unused.
	 *
	 * @param int[] $termInLangIds (wbtl_id)
	 */
	private function cleanTermInLangIdsInner( array $termInLangIds ): void {
		$this->logger->debug(
			'{method}: deleting {count} term_in_lang rows',
			[
				'method' => __METHOD__,
				'count' => count( $termInLangIds ),
			]
		);

		/**
		 * @var $potentiallyUnusedTextInLangIds string[] All of the wbt_text_in_lang ids for the
		 * wbt_term_in_lang ids that we are about to delete.
		 * Once we delete them, those rows MIGHT be orphaned.
		 * If they are orphaned we need to clean them up.
		 */
		$potentiallyUnusedTextInLangIds = $this->selectFieldValuesForPrimaryKey(
			'wbt_term_in_lang',
			'wbtl_text_in_lang_id',
			'wbtl_id',
			$termInLangIds,
			__METHOD__
		);

		$this->incrementForQuery( 'DatabaseTermIdsCleaner_cleanTermInLangIds' );
		$this->dbw->newDeleteQueryBuilder()
			->deleteFrom( 'wbt_term_in_lang' )
			->where( [ 'wbtl_id' => $termInLangIds ] )
			->caller( __METHOD__ )->execute();

		if ( $potentiallyUnusedTextInLangIds === [] ) {
			return;
		}

		/**
		 * This loop checks for wbt_text_in_lang ids that are currently unused, without locking
		 * the rows in the term_in_lang table.
		 *
		 * The unused text_in_lang ids found by this loop, will later be double checked for
		 * use before being locked in the wbt_term_in_lang table.
		 *
		 * This is done to reduce the number of rows that we have to lock, while still ensuring
		 * we never remove an actually used wbt_text_in_lang id
		 */
		$unusedTextInLangIds = [];
		foreach ( $potentiallyUnusedTextInLangIds as  $textInLangId ) {
			// Note: Not batching here is intentional, see T234948
			$stillUsed = $this->dbw->newSelectQueryBuilder()
				->select( 'wbtl_text_in_lang_id' )
				->from( 'wbt_term_in_lang' )
				->where( [ 'wbtl_text_in_lang_id' => $textInLangId ] )
				->caller( __METHOD__ )->fetchField();

			if ( $stillUsed === false ) {
				$unusedTextInLangIds[] = $textInLangId;
			}
		}

		if ( $unusedTextInLangIds === [] ) {
			return;
		}

		$textInLangIdsUsedSinceLastLoopRan = $this->dbw->newSelectQueryBuilder()
			->select( 'wbtl_text_in_lang_id' )
			/**
			 * If we try to clean up a text_in_lang whose last use in a term_in_lang we just
			 * removed, and simultaneously another request adds a new term_in_lang using that
			 * text_in_lang, we want one of the following to happen:
			 *
			 * 1. Our transaction completes first and removes the text_in_lang. The concurrent
			 *    request blocks until we’re done, then sees that the text_in_lang is gone and
			 *    creates it (again) as part of inserting the term_in_lang.
			 * 2. The other transaction completes first and registers another term_in_lang using
			 *    that text_in_lang. We block until that’s done and then notice the text_in_lang
			 *    is still used and don’t clean it up.
			 *
			 * For this to work, we need to use FOR UPDATE when checking whether a
			 * text_in_lang is still used in a term_in_lang, and the other request needs to
			 * ensure during or after insert of the new term_in_lang that the text_in_lang still
			 * exists, or create it otherwise. This way, either our check here or the other
			 * request’s insert will block and wait for the other to complete.
			 */
			->forUpdate()
			->from( 'wbt_term_in_lang' )
			->where( [ 'wbtl_text_in_lang_id' => $unusedTextInLangIds ] )
			->caller( __METHOD__ )->fetchFieldValues();

		/**
		 * If this condition hits, then our extra checks actually stopped the "bad" race condition
		 * from happening.
		 * Currently we assume that it might happen, hence all of the extra logic in the cleaner.
		 */
		if ( count( $textInLangIdsUsedSinceLastLoopRan ) ) {
			$this->logger->info(
				'{method}: found {count} new rows referring to wbtl_text_in_lang_id since check',
				[
					'method' => __METHOD__,
					'count' => count( $textInLangIdsUsedSinceLastLoopRan ),
				]
			);
		}

		$finalUnusedTextInLangIds = array_diff( $unusedTextInLangIds, $textInLangIdsUsedSinceLastLoopRan );
		$this->cleanTextInLangIds( $finalUnusedTextInLangIds );
	}

	/**
	 * Delete the specified wbt_text_in_lang rows from the database,
	 * as well as any text rows that are now unused.
	 *
	 * @param int[] $textInLangIds
	 */
	private function cleanTextInLangIds( array $textInLangIds ): void {
		if ( $textInLangIds === [] ) {
			return;
		}

		$this->logger->debug(
			'{method}: deleting {count} text_in_lang rows',
			[
				'method' => __METHOD__,
				'count' => count( $textInLangIds ),
			]
		);

		$potentiallyUnusedTextIds = $this->selectFieldValuesForPrimaryKey(
			'wbt_text_in_lang',
			'wbxl_text_id',
			'wbxl_id',
			$textInLangIds,
			__METHOD__
		);

		$this->incrementForQuery( 'DatabaseTermIdsCleaner_cleanTextInLangIds' );
		$this->dbw->newDeleteQueryBuilder()
			->deleteFrom( 'wbt_text_in_lang' )
			->where( [ 'wbxl_id' => $textInLangIds ] )
			->caller( __METHOD__ )->execute();

		if ( $potentiallyUnusedTextIds === [] ) {
			return;
		}

		/**
		 * This loop checks for text ids that are currently unused, without locking
		 * the rows in the text_in_lang table.
		 *
		 * The unused text ids found by this loop, will later be double checked for
		 * use before being locked in the text_in_lang table.
		 *
		 * This is done to reduce the number of rows that we have to lock, while still ensuring
		 * we never remove an actually used text_id
		 */
		$unusedTextIds = [];
		foreach ( $potentiallyUnusedTextIds as  $textId ) {
			// Note: Not batching here is intentional, see T234948
			$stillUsed = $this->dbw->newSelectQueryBuilder()
				->select( 'wbxl_text_id' )
				->from( 'wbt_text_in_lang' )
				->where( [ 'wbxl_text_id' => $textId ] )
				->caller( __METHOD__ )->fetchField();

			if ( $stillUsed === false ) {
				$unusedTextIds[] = $textId;
			}
		}

		if ( $unusedTextIds === [] ) {
			return;
		}

		$textIdsUsedSinceLastLoopRan = $this->dbw->newSelectQueryBuilder()
			->select( 'wbxl_text_id' )
			->forUpdate()
			->from( 'wbt_text_in_lang' )
			->where( [ 'wbxl_text_id' => $unusedTextIds ] )
			->caller( __METHOD__ )->fetchFieldValues();

		if ( count( $textIdsUsedSinceLastLoopRan ) ) {
			$this->logger->info(
				'{method}: found {count} new rows referring to wbxl_text_id since check',
				[
					'method' => __METHOD__,
					'count' => count( $textIdsUsedSinceLastLoopRan ),
				]
			);
		}

		$finalUnusedTextIds = array_diff( $unusedTextIds, $textIdsUsedSinceLastLoopRan );
		$this->cleanTextIds( $finalUnusedTextIds );
	}

	/**
	 * Delete the specified text rows from the database.
	 *
	 * @param array $textIds
	 */
	private function cleanTextIds( array $textIds ): void {
		if ( $textIds === [] ) {
			return;
		}

		$this->logger->debug(
			'{method}: deleting {count} text rows',
			[
				'method' => __METHOD__,
				'count' => count( $textIds ),
			]
		);

		$this->incrementForQuery( 'DatabaseTermIdsCleaner_cleanTextIds' );
		$this->dbw->newDeleteQueryBuilder()
			->deleteFrom( 'wbt_text' )
			->where( [ 'wbx_id' => $textIds ] )
			->caller( __METHOD__ )->execute();
	}

	/**
	 * Select the values for a field in rows with the given primary key.
	 * All the rows with these primary keys should exist in the master database,
	 * and the selected values should never change.
	 *
	 * This initially selects from the replica database,
	 * only falling back to the master if the replica did not return
	 * as many rows as there were specified primary key values.
	 *
	 * @param string $table
	 * @param string $selectedVar
	 * @param string $primaryKeyVar
	 * @param int[] $primaryKeyValues
	 * @param string $fname
	 * @return array
	 */
	private function selectFieldValuesForPrimaryKey(
		string $table,
		string $selectedVar,
		string $primaryKeyVar,
		array $primaryKeyValues,
		string $fname = __METHOD__
	): array {
		$values = $this->dbr->newSelectQueryBuilder()
			->select( $selectedVar )
			->from( $table )
			->where( [ $primaryKeyVar => $primaryKeyValues ] )
			->caller( $fname )->fetchFieldValues();

		if ( count( $values ) < count( $primaryKeyValues ) ) {
			$this->logger->debug(
				"{method}: replica only returned {valuesCount} '{selectedVar}' values " .
					"for {primaryKeyValuesCount} '{primaryKeyVar}' values, " .
					'falling back to read from master.',
				[
					'method' => __METHOD__,
					'callingMethod' => $fname,
					'valuesCount' => count( $values ),
					'selectedVar' => $selectedVar,
					'primaryKeyValuesCount' => count( $primaryKeyValues ),
					'primaryKeyVar' => $primaryKeyVar,
				]
			);
			$values = $this->dbw->newSelectQueryBuilder()
				->select( $selectedVar )
				->from( $table )
				->where( [ $primaryKeyVar => $primaryKeyValues ] )
				->caller( $fname )->fetchFieldValues();
		}

		return $values;
	}

}

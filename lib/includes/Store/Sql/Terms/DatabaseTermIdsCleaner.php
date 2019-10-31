<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * Cleans up the normalized term store after some terms are no longer needed.
 * Unused term_in_lang, text_in_lang and text rows are automatically removed.
 * (Unused type rows are never cleaned up.)
 *
 * @license GPL-2.0-or-later
 */
class DatabaseTermIdsCleaner implements TermIdsCleaner {

	/** @var ILoadBalancer */
	private $lb;

	/** @var IDatabase a connection to DB_REPLICA */
	private $dbr = null;

	/** @var IDatabase a connection to DB_MASTER */
	private $dbw = null;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(
		ILoadBalancer $lb,
		LoggerInterface $logger = null
	) {
		$this->lb = $lb;
		// $this->dbr and $this->dbw are lazily initialized in cleanTerms()
		$this->logger = $logger ?: new NullLogger();
	}

	/**
	 * Delete the specified term_in_lang rows from the database,
	 * as well as any text_in_lang and text rows that are now unused.
	 *
	 * It is the caller’s responsibility ensure
	 * that the term_in_lang rows are no longer referenced anywhere;
	 * callers will most likely want to wrap this call in a transaction for that.
	 * On the other hand, this class takes care that text_in_lang and text rows
	 * used by other term_in_lang rows are not removed.
	 *
	 * @param int[] $termIds
	 */
	public function cleanTermIds( array $termIds ) {
		if ( $this->dbr === null ) {
			$this->dbr = $this->lb->getConnection( ILoadBalancer::DB_REPLICA );
			$this->dbw = $this->lb->getConnection( ILoadBalancer::DB_MASTER );
		}

		$this->cleanTermInLangIds( $termIds );
	}

	/**
	 * Delete the specified term_in_lang rows from the database,
	 * as well as any text_in_lang and text rows that are now unused.
	 *
	 * @param int[] $termInLangIds
	 */
	private function cleanTermInLangIds( array $termInLangIds ) {
		if ( $termInLangIds === [] ) {
			return;
		}

		$this->logger->debug(
			'{method}: deleting {count} term_in_lang rows',
			[
				'method' => __METHOD__,
				'count' => count( $termInLangIds ),
			]
		);

		$potentiallyUnusedTextInLangIds = $this->selectFieldValuesForPrimaryKey(
			'wbt_term_in_lang',
			'wbtl_text_in_lang_id',
			'wbtl_id',
			$termInLangIds,
			__METHOD__
		);

		$this->dbw->delete(
			'wbt_term_in_lang',
			[ 'wbtl_id' => $termInLangIds ],
			__METHOD__
		);

		$stillUsedTextInLangIds = $this->dbw->selectFieldValues(
			'wbt_term_in_lang',
			'wbtl_text_in_lang_id',
			[ 'wbtl_text_in_lang_id' => $potentiallyUnusedTextInLangIds ],
			__METHOD__,
			[
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
				 * For this to work, we need to use 'FOR UPDATE' when checking whether a
				 * text_in_lang is still used in a term_in_lang, and the other request needs to
				 * ensure during or after insert of the new term_in_lang that the text_in_lang still
				 * exists, or create it otherwise. This way, either our check here or the other
				 * request’s insert will block and wait for the other to complete.
				 */
				'FOR UPDATE',
				// 'DISTINCT', // not supported in combination with FOR UPDATE on some DB types
			]
		);
		$unusedTextInLangIds = array_diff(
			$potentiallyUnusedTextInLangIds,
			$stillUsedTextInLangIds
		);

		$this->cleanTextInLangIds( $unusedTextInLangIds );
	}

	/**
	 * Delete the specified text_in_lang rows from the database,
	 * as well as any text rows that are now unused.
	 *
	 * @param int[] $textInLangIds
	 */
	private function cleanTextInLangIds( array $textInLangIds ) {
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

		$this->dbw->delete(
			'wbt_text_in_lang',
			[ 'wbxl_id' => $textInLangIds ],
			__METHOD__
		);

		$stillUsedTextIds = $this->dbw->selectFieldValues(
			'wbt_text_in_lang',
			'wbxl_text_id',
			[ 'wbxl_text_id' => $potentiallyUnusedTextIds ],
			__METHOD__,
			[
				'FOR UPDATE', // see comment in cleanTermInLangIds
				// 'DISTINCT', // not supported in combination with FOR UPDATE on some DB types
			]
		);
		$unusedTextIds = array_diff(
			$potentiallyUnusedTextIds,
			$stillUsedTextIds
		);

		$this->cleanTextIds( $unusedTextIds );
	}

	/**
	 * Delete the specified text rows from the database.
	 *
	 * @param array $textIds
	 */
	private function cleanTextIds( array $textIds ) {
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

		$this->dbw->delete(
			'wbt_text',
			[ 'wbx_id' => $textIds ],
			__METHOD__
		);
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
		$table,
		$selectedVar,
		$primaryKeyVar,
		$primaryKeyValues,
		$fname = __METHOD__
	) {
		$values = $this->dbr->selectFieldValues(
			$table,
			$selectedVar,
			[ $primaryKeyVar => $primaryKeyValues ],
			$fname
		);

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
			$values = $this->dbw->selectFieldValues(
				$table,
				$selectedVar,
				[ $primaryKeyVar => $primaryKeyValues ],
				$fname
			);
		}

		return $values;
	}

}

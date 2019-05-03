<?php

namespace Wikibase\Lib\Store\Sql\MediaWikiTermStore;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wikimedia\Rdbms\IDatabase;

/**
 * Cleans up the normalized term store after some terms are no longer needed.
 * Unused term_in_lang, text_in_lang and text rows are automatically removed.
 * (Unused type rows are never cleaned up.)
 *
 * @license GPL-2.0-or-later
 */
class MediaWikiNormalizedTermCleaner {

	/** @var IDatabase */
	private $dbr;

	/** @var IDatabase */
	private $dbw;

	/** @var LoggerInterface */
	private $logger;

	/**
	 * @param IDatabase $dbr a connection to DB_REPLICA
	 * @param IDatabase $dbw a connection to DB_MASTER
	 * @param LoggerInterface|null $logger
	 */
	public function __construct(
		IDatabase $dbr,
		IDatabase $dbw,
		LoggerInterface $logger = null
	) {
		$this->dbr = $dbr;
		$this->dbw = $dbw;
		$this->logger = $logger ?: new NullLogger();
	}

	// TODO the various select() methods can return 'false' on failure, handle that somehow

	/**
	 * Delete the specified term_in_lang rows from the database,
	 * as well as any text_in_lang and text rows that are now unused.
	 *
	 * @param int[] $termInLangIds
	 */
	public function cleanTermInLangIds( array $termInLangIds ) {
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
			[ 'DISTINCT' ]
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
			[ 'DISTINCT' ]
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
					"falling back to read from master.",
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

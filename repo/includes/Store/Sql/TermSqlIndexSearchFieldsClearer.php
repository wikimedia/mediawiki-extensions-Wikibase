<?php

namespace Wikibase\Repo\Store\Sql;

use MediaWiki\MediaWikiServices;
use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Reporting\NullMessageReporter;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\LBFactory;

/**
 * Clears search-related fields in the SQL terms table.
 *
 * @license GPL-2.0-or-later
 * @author Lucas Werkmeister
 */
class TermSqlIndexSearchFieldsClearer {

	const TABLE_NAME = 'wb_terms';

	/**
	 * @var LBFactory
	 */
	private $loadBalancerFactory;

	/**
	 * @var int
	 */
	private $sleep;

	/**
	 * @var MessageReporter
	 */
	private $progressReporter;

	/**
	 * @var MessageReporter
	 */
	private $errorReporter;

	/**
	 * @var int
	 */
	private $batchSize = 1000;

	/**
	 * @var int|null
	 */
	private $fromId = null;

	/**
	 * @var bool
	 */
	private $clearTermWeight = true;

	/**
	 * @param LBFactory $loadBalancerFactory
	 * @param int $sleep Sleep time between each batch
	 */
	public function __construct(
		LBFactory $loadBalancerFactory,
		$sleep = 0
	) {
		$this->loadBalancerFactory = $loadBalancerFactory;
		$this->sleep = $sleep;

		$this->progressReporter = new NullMessageReporter();
		$this->errorReporter = new NullMessageReporter();
	}

	public function setProgressReporter( MessageReporter $reporter ) {
		$this->progressReporter = $reporter;
	}

	public function setErrorReporter( MessageReporter $reporter ) {
		$this->errorReporter = $reporter;
	}

	/**
	 * @param int $size
	 */
	public function setBatchSize( $size ) {
		Assert::parameterType( 'integer', $size, 'size' );

		$this->batchSize = $size;
	}

	/**
	 * @param int $fromId
	 */
	public function setFromId( $fromId ) {
		Assert::parameterType( 'integer', $fromId, 'fromId' );

		$this->fromId = $fromId;
	}

	/**
	 * @param bool $clearTermWeight
	 */
	public function setClearTermWeight( $clearTermWeight ) {
		Assert::parameterType( 'boolean', $clearTermWeight, 'clearTermWeight' );

		$this->clearTermWeight = $clearTermWeight;
	}

	public function clear() {
		$ticket = $this->loadBalancerFactory->getEmptyTransactionTicket( __METHOD__ );
		$loadBalancer = $this->loadBalancerFactory->getMainLB();

		while ( true ) {
			$dbr = $loadBalancer->getConnection( DB_REPLICA );
			$dbw = $loadBalancer->getConnection( DB_MASTER );

			$lastRow = $this->clearBatch( $dbr, $dbw, (int)$this->fromId, $this->batchSize );

			$loadBalancer->reuseConnection( $dbw );
			$loadBalancer->reuseConnection( $dbr );

			$this->loadBalancerFactory->commitAndWaitForReplication( __METHOD__, $ticket );

			if ( $lastRow === false ) {
				break;
			} else {
				$this->fromId = $lastRow + 1;
				$this->progressReporter->reportMessage( 'Cleared up to row ' . $lastRow );
			}

			if ( $this->sleep > 0 ) {
				sleep( $this->sleep );
			}
		}

		$this->progressReporter->reportMessage( 'Done clearing search fields' );
	}

	/**
	 * @param IDatabase $dbr database connection for reading
	 * @param IDatabase $dbw database connection for writing
	 * @param int $fromId start with this row ID
	 * @param int $batchSize clear up to this many rows
	 * @return int|bool the last row ID processed, or false if there were no rows left to clear
	 */
	public function clearBatch( IDatabase $dbr, IDatabase $dbw, $fromId, $batchSize ) {
		$notClearedCondition = 'term_search_key != ""';
		$update = [ 'term_search_key' => '' ];

		if ( $this->clearTermWeight ) {
			$notClearedCondition .= ' OR term_weight != 0.0';
			$update['term_weight'] = 0.0;
		}

		MediaWikiServices::getInstance()->getStatsdDataFactory()->increment(
			'wikibase.repo.wb_terms.selectFieldValues.TermSqlIndexSearchFieldsClearer_clearBatch'
		);

		$rows = $dbr->selectFieldValues(
			self::TABLE_NAME,
			'term_row_id',
			[
				'term_row_id >= ' . (int)$fromId,
				$notClearedCondition,
			],
			__METHOD__,
			[
				'ORDER BY term_row_id',
				'LIMIT' => (int)$batchSize,
			]
		);

		if ( $rows !== [] ) {
			MediaWikiServices::getInstance()->getStatsdDataFactory()->increment(
				'wikibase.repo.wb_terms.update.TermSqlIndexSearchFieldsClearer_clearBatch'
			);

			$dbw->update(
				self::TABLE_NAME,
				$update,
				[
					'term_row_id' => $rows,
				],
				__METHOD__
			);

			return (int)end( $rows );
		} else {
			return false;
		}
	}

}

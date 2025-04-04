<?php

declare( strict_types=1 );

namespace Wikibase\Client\Store\Sql;

use InvalidArgumentException;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\NullMessageReporter;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Lib\Rdbms\ClientDomainDb;
use Wikimedia\Rdbms\ConnectionManager;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * Maintenance helper which adds or updates the "unexpectedUnconnectedPage" page property
 * for all relevant pages.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch <mail@mariushoch.de>
 */
class UnexpectedUnconnectedPagePrimer {

	private ConnectionManager $localConnectionManager;

	private ClientDomainDb $clientDb;

	private NamespaceChecker $namespaceChecker;

	private int $batchSize;

	private int $batchSizeSelectMultiplicator = 1000;

	private MessageReporter $progressReporter;

	private int $position = 0;

	private ?int $maxPageId = null;

	/**
	 * @param ClientDomainDb $clientDb
	 * @param NamespaceChecker $namespaceChecker
	 * @param int $batchSize
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		ClientDomainDb $clientDb,
		NamespaceChecker $namespaceChecker,
		int $batchSize = 1000
	) {
		if ( $batchSize < 1 ) {
			throw new InvalidArgumentException( '$batchSize must be an integer >= 1' );
		}
		$this->clientDb = $clientDb;
		$this->localConnectionManager = $clientDb->connections();
		$this->batchSize = $batchSize;
		$this->namespaceChecker = $namespaceChecker;

		$this->progressReporter = new NullMessageReporter();
	}

	public function setProgressReporter( MessageReporter $messageReporter ): void {
		$this->progressReporter = $messageReporter;
	}

	/**
	 * Set the batch size multiplicator used to determine the number of page ids to
	 * probe when selecting for a batch.
	 */
	public function setBatchSizeSelectMultiplicator( int $batchSizeSelectMultiplicator ): void {
		$this->batchSizeSelectMultiplicator = $batchSizeSelectMultiplicator;
	}

	/**
	 * Set the page ID at which to start processing (inclusive).
	 *
	 * @param int|null $minPageId The page ID, or null for no offset (default).
	 */
	public function setMinPageId( ?int $minPageId ): void {
		$this->position = ( $minPageId ?: 1 ) - 1;
	}

	/**
	 * Set the page ID at which to stop processing (inclusive).
	 * This is a rough measure – up to batch size further pages may be processed.
	 *
	 * @param int|null $maxPageId The page ID, or null for no limit (default).
	 */
	public function setMaxPageId( ?int $maxPageId ): void {
		$this->maxPageId = $maxPageId;
	}

	/**
	 * Add the "unexpectedUnconnectedPage" page prop for all relevant pages.
	 */
	public function setPageProps(): void {
		$highestPageId = $this->getMaximumPageIdToCheck();
		$maxPageId = min( $this->maxPageId ?: PHP_INT_MAX, $highestPageId );

		while ( $this->position < $maxPageId ) {
			$count = $this->processUnexpectedUnconnectedBatch();

			if ( $count > 0 ) {
				$this->progressReporter->reportMessage(
					'Added or updated the "unexpectedUnconnectedPage" page property for ' . $count . ' pages, ' .
					'up to page ID ' . $this->position . ' (inclusive).'
				);
				$this->clientDb->replication()->wait();
				$this->clientDb->autoReconfigure();
			}
		}

		if ( $this->position >= $highestPageId ) {
			$this->progressReporter->reportMessage( 'Done!' );
		} // otherwise, need to re-run with new min/max page ID
	}

	/**
	 * @return int The number of pages affected.
	 */
	private function processUnexpectedUnconnectedBatch(): int {
		$pages = $this->getUnexpectedUnconnectedBatch();

		if ( !$pages ) {
			return 0;
		}

		$count = $this->persistUnexpectedUnconnectedBatch( $pages );
		return $count;
	}

	/**
	 * @param int[][] $pages Page id, page namespace pairs
	 *
	 * @return int The number of pages affected.
	 */
	private function persistUnexpectedUnconnectedBatch( array $pages ): int {
		$rows = $this->makeUnexpectedUnconnectedRows( $pages );

		$dbw = $this->localConnectionManager->getWriteConnection();
		$dbw->startAtomic( __METHOD__ );

		$dbw->newReplaceQueryBuilder()
			->replaceInto( 'page_props' )
			->uniqueIndexFields( [ 'pp_page', 'pp_propname' ] )
			->rows( $rows )
			->caller( __METHOD__ )
			->execute();

		$dbw->endAtomic( __METHOD__ );

		return count( $rows );
	}

	/**
	 * Returns a list of page_props rows for the given pages.
	 *
	 * @param int[][] $pages Page id, page namespace pairs
	 *
	 * @return array[] rows
	 */
	private function makeUnexpectedUnconnectedRows( array $pages ): array {
		$rows = [];

		foreach ( $pages as $page ) {
			$rows[] = [
				'pp_page' => $page[0],
				'pp_propname' => 'unexpectedUnconnectedPage',
				'pp_value' => -$page[1],
				'pp_sortkey' => -$page[1],
			];
		}

		return $rows;
	}

	/**
	 * @return int[][] Page id, page namespace pairs
	 */
	private function getUnexpectedUnconnectedBatch(): array {
		$dbr = $this->localConnectionManager->getReadConnection();

		$lastPosition = $this->position + $this->batchSize * $this->batchSizeSelectMultiplicator;
		$result = $dbr->newSelectQueryBuilder()
			->select( [ 'page_id', 'page_namespace' ] )
			->from( 'page' )
			->leftJoin( 'page_props', null, [
				'page_id = pp_page',
				'pp_propname' => [
					'wikibase_item', 'unexpectedUnconnectedPage', 'expectedUnconnectedPage',
				],
			] )
			->where( [
				'page_namespace' => $this->namespaceChecker->getWikibaseNamespaces(),
				'page_is_redirect' => 0,
				$dbr->expr( 'page_id', '>', $this->position ),
				$dbr->expr( 'page_id', '<=', $lastPosition ),
			] )
			// Either the propname needs to be null (the page prop is not set yet), or the
			// propname matches and the value is larger than 0 (which is the legacy format
			// where we used positive sort keys).
			->andWhere( $dbr->expr( 'pp_propname', '=', null )
				->orExpr(
					$dbr->expr( 'pp_propname', '=', 'unexpectedUnconnectedPage' )
						->and( 'pp_sortkey', '>', 0 )
				) )
			->orderBy( 'page_id', SelectQueryBuilder::SORT_ASC )
			->limit( $this->batchSize )
			->caller( __METHOD__ )->fetchResultSet();
		$pages = [];
		foreach ( $result as $row ) {
			$pages[] = [ $row->page_id, $row->page_namespace ];
		}

		if ( count( $pages ) < $this->batchSize ) {
			$this->position = $lastPosition;
		} else {
			$this->position = intval( end( $pages )[0] );
		}

		return $pages;
	}

	/**
	 * @return int The largest page id we need to bother looking for.
	 */
	private function getMaximumPageIdToCheck(): int {
		// Pages added now are fine anyway, as we assume the new page prop to be active when this
		// script is run.
		return (int)$this->localConnectionManager->getReadConnection()->newSelectQueryBuilder()
			->select( 'MAX(page_id)' )
			->from( 'page' )
			->caller( __METHOD__ )->fetchField();
	}

}

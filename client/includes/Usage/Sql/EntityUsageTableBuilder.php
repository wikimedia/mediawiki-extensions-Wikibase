<?php

namespace Wikibase\Client\Usage\Sql;

use Exception;
use InvalidArgumentException;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Reporting\ExceptionHandler;
use Wikibase\Lib\Reporting\LogWarningExceptionHandler;
use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Reporting\NullMessageReporter;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IResultWrapper;
use Wikimedia\Rdbms\LoadBalancer;

/**
 * Implements initial population (priming) for the wbc_entity_usage table,
 * based on "wikibase_item" entries in the page_props table.
 *
 * All usages will be marked as EntityUsage::ALL_USAGE ("X"), since we do not know
 * which aspects are actually used beyond the sitelinks aspect. The "X" aspect
 * will cause the page to be purged for any kind of change to the respective
 * data item; once the page is re-parse, the "X" aspect would be removed with
 * whatever aspect(s) are actually used on the page.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityUsageTableBuilder {

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var LoadBalancer
	 */
	private $loadBalancer;

	/**
	 * @var string
	 */
	private $usageTableName;

	/**
	 * @var int
	 */
	private $batchSize;

	/**
	 * @var ExceptionHandler
	 */
	private $exceptionHandler;

	/**
	 * @var MessageReporter
	 */
	private $progressReporter;

	/**
	 * @param EntityIdParser $idParser
	 * @param LoadBalancer $loadBalancer
	 * @param int $batchSize defaults to 1000
	 * @param string|null $usageTableName defaults to wbc_entity_usage
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		EntityIdParser $idParser,
		LoadBalancer $loadBalancer,
		$batchSize = 1000,
		$usageTableName = null
	) {
		if ( !is_int( $batchSize ) || $batchSize < 1 ) {
			throw new InvalidArgumentException( '$batchSize must be an integer >= 1' );
		}

		if ( !is_string( $usageTableName ) && $usageTableName !== null ) {
			throw new InvalidArgumentException( '$usageTableName must be a string or null' );
		}

		$this->idParser = $idParser;
		$this->loadBalancer = $loadBalancer;
		$this->batchSize = $batchSize;
		$this->usageTableName = $usageTableName ?: EntityUsageTable::DEFAULT_TABLE_NAME;

		$this->exceptionHandler = new LogWarningExceptionHandler();
		$this->progressReporter = new NullMessageReporter();
	}

	public function setProgressReporter( MessageReporter $progressReporter ) {
		$this->progressReporter = $progressReporter;
	}

	public function setExceptionHandler( ExceptionHandler $exceptionHandler ) {
		$this->exceptionHandler = $exceptionHandler;
	}

	/**
	 * Fill the usage table with rows based on entries in page_props.
	 *
	 * @param int $fromPageId
	 */
	public function fillUsageTable( $fromPageId = 0 ) {
		do {
			$count = $this->processUsageBatch( $fromPageId );
			$this->progressReporter->reportMessage( "Filling usage table: processed $count pages, starting with page #$fromPageId." );
		} while ( $count > 0 );
	}

	/**
	 * @param int &$fromPageId Page ID to start from. Will be updated with the next unprocessed ID,
	 *        to be used as the starting point of the next batch. Pages are processed in order
	 *        of their ID.
	 *
	 * @return int The number of entity usages inserted.
	 */
	private function processUsageBatch( &$fromPageId = 0 ) {
		wfWaitForSlaves();

		$db = $this->loadBalancer->getConnection( DB_MASTER );

		$entityPerPage = $this->getUsageBatch( $db, $fromPageId );

		if ( empty( $entityPerPage ) ) {
			return 0;
		}

		$count = $this->insertUsageBatch( $db, $entityPerPage );

		// Update $fromPageId to become the first page ID of the next batch.
		$fromPageId = max( array_keys( $entityPerPage ) ) + 1;

		$this->loadBalancer->reuseConnection( $db );

		return $count;
	}

	/**
	 * @param IDatabase $db
	 * @param EntityId[] $entityPerPage
	 *
	 * @return int The number of rows inserted.
	 */
	private function insertUsageBatch( IDatabase $db, array $entityPerPage ) {
		$db->startAtomic( __METHOD__ );

		$c = 0;
		foreach ( $entityPerPage as $pageId => $entityId ) {
			$db->insert(
				$this->usageTableName,
				[
					'eu_page_id' => (int)$pageId,
					'eu_aspect' => EntityUsage::ALL_USAGE,
					'eu_entity_id' => $entityId->getSerialization()
				],
				__METHOD__,
				[
					'IGNORE'
				]
			);

			$c++;
		}

		$db->endAtomic( __METHOD__ );
		return $c;
	}

	/**
	 * @param IDatabase $db
	 * @param int $fromPageId
	 *
	 * @return EntityId[] An associative array mapping page IDs to Entity IDs.
	 */
	private function getUsageBatch( IDatabase $db, $fromPageId = 0 ) {
		$res = $db->select(
			'page_props',
			[ 'pp_page', 'pp_value' ],
			[
				'pp_propname' => 'wikibase_item',
				'pp_page >= ' . (int)$fromPageId
			],
			__METHOD__,
			[
				'LIMIT' => $this->batchSize,
				'ORDER BY pp_page'
			]
		);

		return $this->slurpEntityIds( $res );
	}

	/**
	 * @param IResultWrapper $res
	 *
	 * @return EntityId[] An associative array mapping page IDs to Entity IDs.
	 */
	private function slurpEntityIds( IResultWrapper $res ) {
		$entityPerPage = [];

		foreach ( $res as $row ) {
			try {
				$entityId = $this->idParser->parse( $row->pp_value );
				$entityPerPage[$row->pp_page] = $entityId;
			} catch ( Exception $ex ) {
				$this->exceptionHandler->handleException(
					$ex,
					'badEntityId',
					__METHOD__ . ': ' . 'Failed to parse entity ID: ' .
						$row->pp_value . ' at page ' .
						$row->pp_page
				);
			}
		}

		return $entityPerPage;
	}

}

<?php

namespace Wikibase\Repo\Store\Sql;

use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\Sql\TermSqlIndex;
use Wikimedia\Rdbms\LBFactory;

/**
 * (Re)builds term index in the SQL table.
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class TermSqlIndexBuilder {

	const TABLE_NAME = 'wb_terms';

	/**
	 * @var LBFactory
	 */
	private $loadBalancerFactory;

	/**
	 * @var TermSqlIndex
	 */
	private $termSqlIndex;

	/**
	 * @var SqlEntityIdPagerFactory
	 */
	private $entityIdPagerFactory;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var string[]
	 */
	private $entityTypes;

	/**
	 * @var MessageReporter
	 */
	private $reporter;

	/**
	 * @var MessageReporter
	 */
	private $errorReporter;

	/**
	 * @var int
	 */
	private $batchSize;

	/**
	 * @var int|null
	 */
	private $fromId = null;

	/**
	 * @param LBFactory $loadBalancerFactory
	 * @param TermSqlIndex $termSqlIndex
	 * @param SqlEntityIdPagerFactory $entityIdPagerFactory
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param string[] $entityTypes
	 * @param MessageReporter|null $reporter
	 * @param MessageReporter $errorReporter
	 * @param int $batchSize
	 * @param int|null $fromId
	 */
	public function __construct(
		LBFactory $loadBalancerFactory,
		TermSqlIndex $termSqlIndex,
		SqlEntityIdPagerFactory $entityIdPagerFactory,
		EntityRevisionLookup $entityRevisionLookup,
		array $entityTypes,
		MessageReporter $reporter,
		MessageReporter $errorReporter,
		$batchSize = 1000,
		$fromId = null
	) {
		$this->loadBalancerFactory = $loadBalancerFactory;
		$this->termSqlIndex = $termSqlIndex;
		$this->entityIdPagerFactory = $entityIdPagerFactory;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->entityTypes = $entityTypes;
		$this->reporter = $reporter;
		$this->errorReporter = $errorReporter;
		$this->batchSize = $batchSize;
		$this->fromId = $fromId;
	}

	public function rebuild() {
		foreach ( $this->entityTypes as $entityType ) {
			$this->rebuildForEntityType( $entityType );
		}
	}

	/**
	 * @param string $entityType
	 */
	private function rebuildForEntityType( $entityType ) {
		$idPager = $this->entityIdPagerFactory->newSqlEntityIdPager( $entityType );
		$lastIdProcessed = null;

		if ( $this->fromId !== null ) {
			$idPager->setPosition( $this->fromId );
		}

		while ( true ) {
			$entityIds = $idPager->fetchIds( $this->batchSize );

			if ( !$entityIds ) {
				break;
			}

			foreach ( $entityIds as $entityId ) {
				$serializedId = $entityId->getSerialization();
				$lastIdProcessed = $entityId;

				$ticket = $this->loadBalancerFactory->getEmptyTransactionTicket( __METHOD__ );
				$success = $this->termSqlIndex->deleteTermsOfEntity( $entityId );

				if ( !$success ) {
					$this->loadBalancerFactory->rollbackMasterChanges( __METHOD__ );
					$this->errorReporter->reportMessage(
						'Failed to delete terms of entity: ' . $serializedId
					);

					continue;
				}

				$entityRevision = $this->entityRevisionLookup->getEntityRevision( $entityId );
				$success = $this->termSqlIndex->saveTermsOfEntity( $entityRevision->getEntity() );

				if ( !$success ) {
					$this->loadBalancerFactory->rollbackMasterChanges( __METHOD__ );
					$this->errorReporter->reportMessage(
						"Failed to save terms of entity: $serializedId"
					);

					continue;
				}

				$this->loadBalancerFactory->commitAndWaitForReplication( __METHOD__, $ticket );
			}

			if ( $lastIdProcessed !== null ) {
				$this->reporter->reportMessage( "Processed up to $serializedId" );
			}
		}
	}

}

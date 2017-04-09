<?php

namespace Wikibase\Repo\Store\Sql;

use Wikibase\DataModel\Entity\Int32EntityId;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\Sql\TermSqlIndex;
use Wikimedia\Rdbms\LBFactory;

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
	 * @var ObservableMessageReporter
	 */
	private $reporter;

	/**
	 * @var int
	 */
	private $batchSize;

	/**
	 * @var string|null
	 */
	private $entityType = null;

	/**
	 * @var Int32EntityId|null
	 */
	private $startId = null;

	public function __construct(
		LBFactory $loadBalancerFactory,
		TermSqlIndex $termSqlIndex,
		SqlEntityIdPagerFactory $entityIdPagerFactory,
		EntityRevisionLookup $entityRevisionLookup,
		ObservableMessageReporter $reporter,
		$batchSize = 1000
	) {
		$this->loadBalancerFactory = $loadBalancerFactory;
		$this->termSqlIndex = $termSqlIndex;
		$this->entityIdPagerFactory = $entityIdPagerFactory;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->reporter = $reporter;
		$this->batchSize = $batchSize;
	}

	/**
	 * @param string $entityType
	 */
	public function setEntityType( $entityType ) {
		$this->entityType = $entityType;
	}

	/**
	 * @param Int32EntityId $startId
	 * @throws \RuntimeException
	 */
	public function setStartId( Int32EntityId $startId ) {
		if ( $this->entityType === null ) {
			throw new \RuntimeException( '$startId can only be used together with $entityType, '
				. 'but $entityType is not set' );
		}

		if ( $startId->getEntityType() !== $this->entityType ) {
			throw new \RuntimeException( '$startId does not match entity type option: '
				. $this->entityType );
		}

		$this->startId = $startId;
	}

	public function rebuild() {
		foreach ( $this->getEntityTypes() as $entityType ) {
			$this->rebuildForEntityType( $entityType );
		}

		$this->reporter->reportMessage( 'Done' );
	}

	/**
	 * @param string $entityType
	 */
	private function rebuildForEntityType( $entityType ) {
		$idPager = $this->entityIdPagerFactory->newSqlEntityIdPager( $entityType );
		$lastIdProcessed = null;

		if ( $this->startId !== null ) {
			$numericStartId = $this->startId->getNumericId();
		}

		while ( true ) {
			$this->loadBalancerFactory->waitForReplication();

			$entityIds = $idPager->fetchIds( $this->batchSize );

			if ( !$entityIds ) {
				break;
			}

			foreach ( $entityIds as $entityId ) {
				if ( isset( $numericStartId ) && ! ( $entityId instanceof Int32EntityId ) ) {
					throw new \RuntimeException( '$startId only works for entity types with ' .
						'numeric ids. EntityId of type ' . $entityId->getEntityType() . ' found.'
					);
				}

				if ( isset( $numericStartId ) && $entityId->getNumericId() < $numericStartId ) {
					continue;
				}

				$entityRevision = $this->entityRevisionLookup->getEntityRevision( $entityId );
				$success = $this->termSqlIndex->saveTermsOfEntity( $entityRevision->getEntity() );
				$lastIdProcessed = $entityId;

				if ( !$success ) {
					wfLogWarning( 'Failed to save terms of entity: ' .
					              $entityId->getSerialization() );
				}
			}

			if ( $lastIdProcessed !== null ) {
				$serializedId = $entityId->getSerialization();
				$this->reporter->reportMessage( "Updated to $serializedId" );
			}
		}
	}

	/**
	 * @return string[]
	 */
	private function getEntityTypes() {
		if ( $this->entityType !== null ) {
			$entityTypes = [ $this->entityType ];
		} else {
			$entityTypes = $this->getAllEntityTypes();
		}

		return $entityTypes;
	}

	/**
	 * @return string[]
	 */
	private function getAllEntityTypes() {
		$dbw = $this->loadBalancerFactory->getMainLB()->getConnection( DB_MASTER );
		$rows = $dbw->select( self::TABLE_NAME, 'DISTINCT term_entity_type', [], __METHOD__ );

		$entityTypes = [];

		foreach ( $rows as $row ) {
			$entityTypes[] = $row->term_entity_type;
		}

		return $entityTypes;
	}

}

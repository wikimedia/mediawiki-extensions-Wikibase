<?php

namespace Wikibase\Repo\Store\Sql;

use RuntimeException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Int32EntityId;
use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Reporting\NullMessageReporter;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\Sql\TermSqlIndex;
use Wikibase\TermIndexEntry;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\LBFactory;
use Wikimedia\Rdbms\LoadBalancer;

/**
 * (Re)builds term index in the SQL table.
 * This can add missing information to the SQL table like missing full entity ID. It also removes
 * possible duplicate terms.
 * It can also ensure that all expected entity terms are stored in the term index, i.e. add
 * all possible missing terms of the given entity, and remove all possible no longer valid
 * terms of the entity, even if there is no other need for rebuilding the index
 * (i.e. all ID fields are populated, there are no duplicate entries).
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
	 * @var bool
	 */
	private $readFullEntityIdColumn = true;

	/**
	 * @var int
	 */
	private $batchSize = 1000;

	/**
	 * @var bool
	 */
	private $removeDuplicateTerms = false;

	/**
	 * @var bool
	 */
	private $rebuildAllEntityTerms = false;

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
	 * @param int $sleep Sleep time between each batch
	 */
	public function __construct(
		LBFactory $loadBalancerFactory,
		TermSqlIndex $termSqlIndex,
		SqlEntityIdPagerFactory $entityIdPagerFactory,
		EntityRevisionLookup $entityRevisionLookup,
		array $entityTypes,
		$sleep = 0
	) {
		$this->loadBalancerFactory = $loadBalancerFactory;
		$this->termSqlIndex = $termSqlIndex;
		$this->entityIdPagerFactory = $entityIdPagerFactory;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->entityTypes = $entityTypes;
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
		$this->batchSize = $size;
	}

	/**
	 * @param bool $readFullEntityIdColumn
	 */
	public function setReadFullEntityIdColumn( $readFullEntityIdColumn ) {
		$this->readFullEntityIdColumn = $readFullEntityIdColumn;
	}

	public function rebuild() {
		foreach ( $this->entityTypes as $entityType ) {
			$this->rebuildForEntityType( $entityType );
		}
	}

	/**
	 * @param int $fromId
	 */
	public function setFromId( $fromId ) {
		Assert::parameterType( 'integer', $fromId, 'fromId' );

		$this->fromId = $fromId;
	}

	/**
	 * Enables or disables the complete rebuilding of entity terms, i.e. removing all existing
	 * terms of the entity, and adding its terms to the index again.
	 *
	 * @param bool $rebuildAllEntityTerms
	 */
	public function setRebuildAllEntityTerms( $rebuildAllEntityTerms ) {
		$this->rebuildAllEntityTerms = $rebuildAllEntityTerms;
	}

	/**
	 * Enables or disables checking if the index contains duplicate terms for the particular
	 * entity, and removing duplicates if found any.
	 *
	 * Note this setting is redundant if the complete rebuild of the entity term index is enabled.
	 *
	 * @param bool $removeDuplicateTerms
	 */
	public function setRemoveDuplicateTerms( $removeDuplicateTerms ) {
		$this->removeDuplicateTerms = $removeDuplicateTerms;
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

		$ticket = $this->loadBalancerFactory->getEmptyTransactionTicket( __METHOD__ );
		$loadBalancer = $this->loadBalancerFactory->getMainLB();

		while ( true ) {
			$entityIds = $idPager->fetchIds( $this->batchSize );

			if ( !$entityIds ) {
				break;
			}

			$this->rebuildTermsForBatch( $entityIds, $loadBalancer, $ticket );
			$this->progressReporter->reportMessage( 'Processed up to page '
				. $idPager->getPosition() . ' (' . end( $entityIds ) . ')' );

			if ( $this->sleep > 0 ) {
				sleep( $this->sleep );
			}
		}

		$this->progressReporter->reportMessage( "Done rebuilding $entityType terms" );
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param LoadBalancer $loadBalancer
	 * @param mixed $transactionTicket
	 */
	private function rebuildTermsForBatch( array $entityIds, LoadBalancer $loadBalancer, $transactionTicket ) {
		$dbr = $loadBalancer->getConnection( DB_REPLICA );
		$dbw = $loadBalancer->getConnection( DB_MASTER );

		foreach ( $entityIds as $entityId ) {
			$this->rebuildEntityTerms( $dbr, $dbw, $entityId );
		}

		$loadBalancer->reuseConnection( $dbw );
		$loadBalancer->reuseConnection( $dbr );

		$this->loadBalancerFactory->commitAndWaitForReplication( __METHOD__, $transactionTicket );
	}

	private function rebuildEntityTerms( IDatabase $dbr, IDatabase $dbw, EntityId $entityId ) {
		if ( $this->rebuildAllEntityTerms ) {
			$this->rebuildAllTermsOfEntity( $entityId );
			return;
		}

		if ( $this->removeDuplicateTerms ) {
			$existingTerms = $this->termSqlIndex->getTermsOfEntity( $entityId );
			$duplicateTerms = $this->getDuplicateTerms( $existingTerms );
			if ( $duplicateTerms ) {
				$this->removeDuplicateTermsOfEntity( $dbw, $entityId, $duplicateTerms );
			}
		}

		if ( $this->hasMissingFullEntityId( $dbr, $entityId ) ) {
			$this->populateFullEntityIdField( $dbw, $entityId );
		}
	}

	private function rebuildAllTermsOfEntity( EntityId $entityId ) {
		$serializedId = $entityId->getSerialization();

		$success = $this->termSqlIndex->deleteTermsOfEntity( $entityId );

		if ( !$success ) {
			$this->loadBalancerFactory->rollbackMasterChanges( __METHOD__ );
			$this->errorReporter->reportMessage(
				"Failed to delete terms of entity: $serializedId"
			);

			return;
		}

		$entityRevision = $this->entityRevisionLookup->getEntityRevision( $entityId );
		$success = $this->termSqlIndex->saveTermsOfEntity( $entityRevision->getEntity() );

		if ( !$success ) {
			$this->loadBalancerFactory->rollbackMasterChanges( __METHOD__ );
			$this->errorReporter->reportMessage(
				"Failed to save terms of entity: $serializedId"
			);
		}
	}

	/**
	 * @param IDatabase $db
	 * @param EntityId $entityId
	 * @param TermIndexEntry[] $duplicateTerms
	 */
	private function removeDuplicateTermsOfEntity( IDatabase $db, EntityId $entityId, array $duplicateTerms ) {
		$idConds = $this->readFullEntityIdColumn ?
			[ 'term_full_entity_id' => $entityId->getSerialization() ] :
			[
				'term_entity_id' => $entityId->getNumericId(),
				'term_entity_type' => $entityId->getEntityType(),
			];

		/** @var TermIndexEntry $term */
		foreach ( $duplicateTerms as $term ) {
			$rowIds = $db->selectFieldValues(
				self::TABLE_NAME,
				'term_row_id',
				array_merge(
					$idConds,
					[
						'term_language' => $term->getLanguage(),
						'term_type' => $term->getTermType(),
						'term_text' => $term->getText(),
					]
				),
				__METHOD__
			);

			if ( !$rowIds ) {
				continue;
			}

			array_shift( $rowIds );
			foreach ( $rowIds as $id ) {
				$db->delete( self::TABLE_NAME, [ 'term_row_id' => $id ] );
			}
		}
	}

	/**
	 * @param TermIndexEntry[] $terms
	 *
	 * @return TermIndexEntry[]
	 */
	private function getDuplicateTerms( array $terms ) {
		$duplicateTerms = [];

		foreach ( $terms as $index => $term ) {
			foreach ( $terms as $otherIndex => $otherTerm ) {
				if ( $index === $otherIndex ) {
					continue;
				}

				if ( TermIndexEntry::compare( $term, $otherTerm ) === 0 ) {
					$key = implode(
						':',
						[ $term->getLanguage(), $term->getTermType(), $term->getText() ]
					);

					$duplicateTerms[$key] = $term;
				}
			}
		}

		return array_values( $duplicateTerms );
	}

	private function hasMissingFullEntityId( IDatabase $db, EntityId $entityId ) {
		if ( $this->readFullEntityIdColumn ) {
			return false;
		}

		if ( ! $entityId instanceof Int32EntityId ) {
			throw new RuntimeException(
				'Full entity ID column in wb_terms table is not used but ' .
				$entityId->getSerialization() . ' does not have numeric part in ID.'
			);
		}

		$hasRowWithNullFullId = (bool)$db->selectField(
			self::TABLE_NAME,
			'1',
			[
				'term_entity_type' => $entityId->getEntityType(),
				'term_entity_id' => $entityId->getNumericId(),
				'term_full_entity_id IS NULL'
			],
			__METHOD__
		);

		return (bool)$hasRowWithNullFullId;
	}

	private function populateFullEntityIdField( IDatabase $db, EntityId $entityId ) {
		if ( $this->readFullEntityIdColumn ) {
			return;
		}

		if ( ! $entityId instanceof Int32EntityId ) {
			throw new RuntimeException(
				'Full entity ID column in wb_terms table is not used but ' .
				$entityId->getSerialization() . ' does not have numeric part in ID.'
			);
		}

		$db->update(
			self::TABLE_NAME,
			[
				'term_full_entity_id' => $entityId->getSerialization()
			],
			[
				'term_entity_type' => $entityId->getEntityType(),
				'term_entity_id' => $entityId->getNumericId(),
			],
			__METHOD__
		);
	}

}

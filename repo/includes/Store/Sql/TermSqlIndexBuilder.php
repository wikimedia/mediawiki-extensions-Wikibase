<?php

namespace Wikibase\Repo\Store\Sql;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Int32EntityId;
use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\Sql\TermSqlIndex;
use Wikibase\TermIndexEntry;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\LBFactory;

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
	private $writeFullEntityIdColumn;

	/**
	 * @var bool
	 */
	private $readFullEntityIdColumn;

	/**
	 * @var int
	 */
	private $batchSize;

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
	 * @param MessageReporter $progressReporter
	 * @param MessageReporter $errorReporter
	 * @param int $batchSize
	 * @param bool $writeFullEntityIdColumn
	 * @param bool $readFullEntityIdColumn
	 */
	public function __construct(
		LBFactory $loadBalancerFactory,
		TermSqlIndex $termSqlIndex,
		SqlEntityIdPagerFactory $entityIdPagerFactory,
		EntityRevisionLookup $entityRevisionLookup,
		array $entityTypes,
		MessageReporter $progressReporter,
		MessageReporter $errorReporter,
		$batchSize = 1000,
		$writeFullEntityIdColumn = true,
		$readFullEntityIdColumn = false
	) {
		$this->loadBalancerFactory = $loadBalancerFactory;
		$this->termSqlIndex = $termSqlIndex;
		$this->entityIdPagerFactory = $entityIdPagerFactory;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->entityTypes = $entityTypes;
		$this->progressReporter = $progressReporter;
		$this->errorReporter = $errorReporter;
		$this->batchSize = $batchSize;
		$this->writeFullEntityIdColumn = $writeFullEntityIdColumn;
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
	 * Makes the builder rebuild all entity terms, i.e. it will check if any of entity terms
	 * is missing, and/or any of existing entity terms is no longer "correct".
	 * Missing terms will be added, and no longer expected terms will be removed.
	 */
	public function setRebuildAllEntityTerms() {
		$this->rebuildAllEntityTerms = true;
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
				$lastIdProcessed = $entityId;

				$this->rebuildEntityTerms( $entityId );
			}

			if ( $lastIdProcessed !== null ) {
				$this->progressReporter->reportMessage( "Processed up to page "
					. $idPager->getPosition() . " ($lastIdProcessed)" );
			}
		}

		$this->progressReporter->reportMessage( "Done rebuilding $entityType terms" );
	}

	private function rebuildEntityTerms( EntityId $entityId ) {
		$serializedId = $entityId->getSerialization();

		if ( !$this->needsTermRebuild( $entityId ) ) {
			return;
		}

		$ticket = $this->loadBalancerFactory->getEmptyTransactionTicket( __METHOD__ );
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

			return;
		}

		$this->loadBalancerFactory->commitAndWaitForReplication( __METHOD__, $ticket );
	}

	/**
	 * @param EntityId $entityId
	 * @return bool
	 */
	private function needsTermRebuild( EntityId $entityId ) {
		$existingTerms = $this->termSqlIndex->getTermsOfEntity( $entityId );

		$termsChanged = false;

		if ( $this->rebuildAllEntityTerms ) {
			$entityRevision = $this->entityRevisionLookup->getEntityRevision( $entityId );
			$entity = $entityRevision->getEntity();

			$rebuiltTerms = $this->termSqlIndex->getEntityTerms( $entity );

			$termsToInsert = array_udiff( $rebuiltTerms, $existingTerms, [ TermIndexEntry::class, 'compare' ] );
			$termsToDelete = array_udiff( $existingTerms, $rebuiltTerms, [ TermIndexEntry::class, 'compare' ] );

			$termsChanged = $termsToInsert || $termsToDelete;
		}

		$needToPopulateEntityIdColumn = !$this->readFullEntityIdColumn &&
			$this->writeFullEntityIdColumn &&
			$this->hasMissingFullEntityId( $entityId );

		return $termsChanged || $this->containsDuplicates( $existingTerms ) || $needToPopulateEntityIdColumn;
	}

	/**
	 * @param TermIndexEntry[] $terms
	 * @return bool
	 */
	private function containsDuplicates( array $terms ) {
		foreach ( $terms as $index => $term ) {
			foreach ( $terms as $otherIndex => $otherTerm ) {
				if ( $index === $otherIndex ) {
					continue;
				}

				if ( TermIndexEntry::compare( $term, $otherTerm ) === 0 ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @param EntityId $entityId
	 * @return bool
	 */
	private function hasMissingFullEntityId( EntityId $entityId ) {
		if ( ! $entityId instanceof Int32EntityId ) {
			return false;
		}

		$db = $this->loadBalancerFactory->getMainLB()->getConnection( DB_REPLICA );

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

		return $hasRowWithNullFullId;
	}

}

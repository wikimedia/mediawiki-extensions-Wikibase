<?php

namespace Wikibase\Lib\Changes;

use MWException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListHolder;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\DataModel\Term\TermList;
use Wikibase\EntityChange;

/**
 * Factory for EntityChange objects
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityChangeFactory {

	/**
	 * @var EntityDiffer
	 */
	private $entityDiffer;

	/**
	 * @var string[] Maps entity type IDs to subclasses of EntityChange.
	 */
	private $changeClasses;

	/**
	 * @param EntityDiffer $entityDiffer
	 * @param string[] $changeClasses Maps entity type IDs to subclasses of EntityChange.
	 * Entity types not mapped explicitly are assumed to use EntityChange itself.
	 */
	public function __construct(
		EntityDiffer $entityDiffer,
		array $changeClasses = array()
	) {
		$this->entityDiffer = $entityDiffer;
		$this->changeClasses = $changeClasses;
	}

	/**
	 * @since 0.5
	 *
	 * @param string $action The action name
	 * @param EntityId $entityId
	 * @param array $fields additional fields to set
	 *
	 * @return EntityChange
	 */
	public function newForEntity( $action, EntityId $entityId, array $fields = array() ) {
		$entityType = $entityId->getEntityType();

		if ( isset( $this->changeClasses[ $entityType ] ) ) {
			$class = $this->changeClasses[$entityType];
		} else {
			$class = EntityChange::class;
		}

		/** @var EntityChange $instance */
		$instance = new $class( $fields );

		$instance->setEntityId( $entityId );

		if ( !$instance->hasField( 'info' ) ) {
			$instance->setField( 'info', [] );
		}

		// Note: the change type determines how newForChangeType will
		// instantiate and handle the change
		$type = 'wikibase-' . $entityId->getEntityType() . '~' . $action;
		$instance->setField( 'type', $type );

		return $instance;
	}

	/**
	 * @since 0.5
	 *
	 * @param string $changeType
	 * @param EntityId $entityId
	 * @param array $fields additional fields to set
	 *
	 * @return EntityChange
	 */
	public function newForChangeType( $changeType, EntityId $entityId, array $fields ) {
		$action = explode( '~', $changeType )[1];
		return $this->newForEntity( $action, $entityId, $fields );
	}

	/**
	 * Constructs an EntityChange from the given old and new Entity.
	 *
	 * @since 0.5
	 *
	 * @param string      $action The action name
	 * @param EntityDocument|null $oldEntity
	 * @param EntityDocument|null $newEntity
	 * @param array $fields additional fields to set
	 *
	 * @return EntityChange
	 * @throws MWException
	 */
	public function newFromUpdate(
		$action,
		EntityDocument $oldEntity = null,
		EntityDocument $newEntity = null,
		array $fields = array()
	) {
		if ( $oldEntity === null && $newEntity === null ) {
			throw new MWException( 'Either $oldEntity or $newEntity must be given' );
		}

		$this->minimizeEntityForDiffing( $oldEntity );
		$this->minimizeEntityForDiffing( $newEntity );

		if ( $oldEntity === null ) {
			$id = $newEntity->getId();
			$diff = $this->entityDiffer->getConstructionDiff( $newEntity );
		} elseif ( $newEntity === null ) {
			$id = $oldEntity->getId();
			$diff = $this->entityDiffer->getDestructionDiff( $oldEntity );
		} elseif ( $oldEntity->getType() !== $newEntity->getType() ) {
			throw new MWException( 'Entity type mismatch' );
		} else {
			$id = $newEntity->getId();
			$diff = $this->entityDiffer->diffEntities( $oldEntity, $newEntity );
		}

		/** @var EntityChange $instance */
		$instance = self::newForEntity( $action, $id, $fields );
		$instance->setDiff( $diff );

		return $instance;
	}

	/**
	 * Hack: Don't include statement, description and alias diffs, since those are unused and not
	 * helpful performance-wise to the dispatcher and change handling.
	 *
	 * @fixme Implement T113468 and remove this.
	 *
	 * @param EntityDocument $entity
	 */
	private function minimizeEntityForDiffing( EntityDocument $entity = null ) {
		if ( $entity instanceof StatementListHolder ) {
			$entity->setStatements( new StatementList() );
		} elseif ( $entity instanceof StatementListProvider ) {
			$statements = $entity->getStatements();

			foreach ( $statements->toArray() as $statement ) {
				$statements->removeStatementsWithGuid( $statement->getGuid() );
			}
		}

		if ( $entity instanceof FingerprintProvider ) {
			$fingerprint = $entity->getFingerprint();

			$fingerprint->setDescriptions( new TermList() );
			$fingerprint->setAliasGroups( new AliasGroupList() );
		}
	}

}

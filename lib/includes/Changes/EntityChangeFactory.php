<?php

namespace Wikibase\Lib\Changes;

use InvalidArgumentException;
use MWException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\EntityChange;
use Wikimedia\Assert\Assert;

/**
 * Factory for EntityChange objects
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityChangeFactory {

	/**
	 * @var EntityDiffer
	 */
	private $entityDiffer;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var string[] Maps entity type IDs to subclasses of EntityChange.
	 */
	private $changeClasses;

	/**
	 * @param EntityDiffer $entityDiffer
	 * @param EntityIdParser $idParser
	 * @param string[] $changeClasses Maps entity type IDs to subclasses of EntityChange.
	 * Entity types not mapped explicitly are assumed to use EntityChange itself.
	 */
	public function __construct(
		EntityDiffer $entityDiffer,
		EntityIdParser $idParser,
		array $changeClasses
	) {
		$this->entityDiffer = $entityDiffer;
		$this->idParser = $idParser;
		$this->changeClasses = $changeClasses;
	}

	/**
	 * @param string $action The action name
	 * @param EntityId $entityId
	 * @param array $fields additional fields to set
	 *
	 * @return EntityChange
	 */
	public function newForEntity( $action, EntityId $entityId, array $fields = [] ) {
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
	 * @param string $changeType
	 * @param EntityId $entityId
	 * @param array $fields additional fields to set
	 *
	 * @throws InvalidArgumentException
	 * @return EntityChange
	 */
	public function newForChangeType( $changeType, EntityId $entityId, array $fields ) {
		$changeType = explode( '~', $changeType, 2 );
		Assert::parameter(
			isset( $changeType[1] ),
			'$changeType',
			'must conform to the format "wikibase-<entityType>~<action>"'
		);

		$action = $changeType[1];
		return $this->newForEntity( $action, $entityId, $fields );
	}

	/**
	 * @param array $fields all data fields, including at least 'type' and 'object_id'.
	 *
	 * @throws InvalidArgumentException
	 * @return EntityChange
	 */
	public function newFromFieldData( array $fields ) {
		Assert::parameter( isset( $fields['type'] ), '$fields[\'type\']', 'must be set' );
		Assert::parameter( isset( $fields['object_id'] ), '$fields[\'object_id\']', 'must be set' );

		$entityId = $this->idParser->parse( $fields['object_id'] );
		return $this->newForChangeType( $fields['type'], $entityId, $fields );
	}

	/**
	 * Constructs an EntityChange from the given old and new Entity.
	 *
	 * @param string      $action The action name
	 * @param EntityDocument|null $oldEntity
	 * @param EntityDocument|null $newEntity
	 *
	 * @return EntityChange
	 * @throws MWException
	 */
	public function newFromUpdate(
		$action,
		EntityDocument $oldEntity = null,
		EntityDocument $newEntity = null
	) {
		if ( $oldEntity === null && $newEntity === null ) {
			throw new MWException( 'Either $oldEntity or $newEntity must be given' );
		}

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

		$instance = $this->newForEntity( $action, $id );
		$aspectsDiff = EntityDiffChangedAspects::newFromEntityDiff( $diff );
		$instance->setCompactDiff( $aspectsDiff );

		return $instance;
	}

}

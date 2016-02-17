<?php

namespace Wikibase\Lib\Changes;

use MWException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListHolder;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\DataModel\Term\TermList;
use Wikibase\EntityChange;
use Wikimedia\Assert\Assert;

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
	 * @param callable[] $changeFactoryCallbacks Maps entity type IDs to callbacks returning instances of EntityChange.
	 * Entity types not mapped explicitly are assumed to use EntityChange itself.
	 */
	public function __construct(
		EntityDiffer $entityDiffer,
		array $changeFactoryCallbacks = array()
	) {
		Assert::parameterElementType( 'callable', $changeFactoryCallbacks, '$changeFactoryCallbacks' );

		$this->changeFactoryCallbacks = $changeFactoryCallbacks;
		$this->entityDiffer = $entityDiffer;
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

		if ( isset( $this->changeFactoryCallbacks[$entityType ] ) ) {
			$entityChange = call_user_func( $this->changeFactoryCallbacks[$entityType], $fields );

			Assert::postcondition(
				$entityChange instanceof EntityChange,
				'Callback must return an instance of EntityChange'
			);
		} else {
			$entityChange = new EntityChange( $fields );
		}

		if ( !$entityChange->hasField( 'object_id' ) ) {
			$entityChange->setField( 'object_id', $entityId->getSerialization() );
		}

		if ( !$entityChange->hasField( 'info' ) ) {
			$entityChange->setField( 'info', array() );
		}

		// Note: the change type determines how the client will
		// instantiate and handle the change
		$type = 'wikibase-' . $entityId->getEntityType() . '~' . $action;
		$entityChange->setField( 'type', $type );

		return $entityChange;
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
		}

		if ( $entity instanceof FingerprintProvider ) {
			$fingerprint = $entity->getFingerprint();

			$fingerprint->setDescriptions( new TermList() );
			$fingerprint->setAliasGroups( new AliasGroupList() );
		}
	}

}

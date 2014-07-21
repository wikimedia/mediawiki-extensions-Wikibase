<?php

namespace Wikibase\Lib\Changes;

use InvalidArgumentException;
use MWException;
use Revision;
use User;
use Wikibase\ChangesTable;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityChange;
use Wikibase\EntityContent;
use Wikibase\EntityFactory;

/**
 * Factory for EntityChange objects
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityChangeFactory {

	/**
	 * @var array maps entity type IDs to subclasses of EntityChange
	 */
	private $changeClasses;

	/**
	 * @var ChangesTable
	 */
	private $changesTable;

	/**
	 * @var EntityFactory
	 */
	private $entityFactory;

	/**
	 * @param ChangesTable $changesTable
	 * @param EntityFactory $entityFactory
	 * @param array $changeClasses maps entity type IDs to subclasses of EntityChange.
	 * Entity types not mapped explicitly are assumed to use EntityChange itself.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( ChangesTable $changesTable, EntityFactory $entityFactory, array $changeClasses = array() ) {
		$this->changeClasses = $changeClasses;
		$this->changesTable = $changesTable;
		$this->entityFactory = $entityFactory;
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
	public function newForEntity( $action, EntityId $entityId, array $fields = null ) {
		$entityType = $entityId->getEntityType();

		if ( isset( $this->changeClasses[ $entityType ] ) ) {
			$class = $this->changeClasses[$entityType];
		} else {
			$class = '\Wikibase\EntityChange';
		}

		/** @var EntityChange $instance  */
		$instance = new $class(
			$this->changesTable,
			$fields,
			true
		);

		if ( !$instance->hasField( 'object_id' ) ) {
			$instance->setField( 'object_id', $entityId->getPrefixedId() );
		}

		if ( !$instance->hasField( 'info' ) ) {
			$info = array();
			$instance->setField( 'info', $info );
		}

		// Note: the change type determines how the client will
		// instantiate and handle the change
		$type = 'wikibase-' . $entityId->getEntityType() . '~' . $action;
		$instance->setField( 'type', $type );

		return $instance;
	}

	/**
	 * Constructs an EntityChange from the given old and new Entity.
	 *
	 * @since 0.5
	 *
	 * @param string      $action The action name
	 * @param Entity|null $oldEntity
	 * @param Entity|null $newEntity
	 * @param array|null  $fields additional fields to set
	 *
	 * @return EntityChange
	 * @throws MWException
	 */
	public function newFromUpdate( $action, Entity $oldEntity = null, Entity $newEntity = null, array $fields = null ) {
		if ( $oldEntity === null && $newEntity === null ) {
			throw new MWException( 'Either $oldEntity or $newEntity must be give.' );
		}

		if ( $oldEntity === null ) {
			$oldEntity = $this->entityFactory->newEmpty( $newEntity->getType() );
			$theEntity = $newEntity;
		} elseif ( $newEntity === null ) {
			$newEntity = $this->entityFactory->newEmpty( $oldEntity->getType() );
			$theEntity = $oldEntity;
		} elseif ( $oldEntity->getType() !== $newEntity->getType() ) {
			throw new MWException( 'Entity type mismatch' );
		} else {
			$theEntity = $newEntity;
		}

		/**
		 * @var EntityChange $instance
		 */
		$diff = $oldEntity->getDiff( $newEntity );
		$instance = self::newForEntity( $action, $theEntity->getId(), $fields );
		$instance->setDiff( $diff );
		$instance->setEntity( $theEntity );

		return $instance;
	}

	/**
	 * @see ChangeNotifier::notifyOnPageDeleted
	 */
	public function getOnPageDeletedChange( EntityContent $content, User $user, $timestamp ) {
		wfProfileIn( __METHOD__ );

		if ( $content->isRedirect() ) {
			// TODO: notify the client about changes to redirects!
			return null;
		}

		$change = $this->newFromUpdate( EntityChange::REMOVE, $content->getEntity() );

		$change->setTimestamp( $timestamp );
		$change->setUserId( $user->getId() );
		$change->setMetadataFromUser( $user );

		wfProfileOut( __METHOD__ );
		return $change;
	}

	/**
	 * @see ChangeNotifier::notifyOnPageUndeleted
	 */
	public function getOnPageUndeletedChange( Revision $revision ) {
		wfProfileIn( __METHOD__ );

		/** @var EntityContent $content */
		$content = $revision->getContent();

		if ( $content->isRedirect() ) {
			// TODO: notify the client about changes to redirects!
			return null;
		}

		$change = $this->newFromUpdate( EntityChange::RESTORE, null, $content->getEntity() );

		$change->setRevisionInfo( $revision );

		$user = User::newFromId( $revision->getUser() );
		$change->setMetadataFromUser( $user );

		wfProfileOut( __METHOD__ );
		return $change;
	}

	/**
	 * @see ChangeNotifier::notifyOnPageCreated
	 */
	public function getOnPageCreatedChange( Revision $revision ) {
		wfProfileIn( __METHOD__ );

		/** @var EntityContent $content */
		$content = $revision->getContent();

		if ( $content->isRedirect() ) {
			// Clients currently don't care about redirected being created.
			// TODO: notify the client about changes to redirects!
			return null;
		}

		$change = $this->newFromUpdate( EntityChange::ADD, null, $content->getEntity() );

		$change->setRevisionInfo( $revision );

		wfProfileOut( __METHOD__ );
		return $change;
	}

	/**
	 * @see ChangeNotifier::notifyOnPageModified
	 */
	public function getOnPageModifiedChange( Revision $current, Revision $parent ) {
		wfProfileIn( __METHOD__ );

		if ( $current->getParentId() !== $parent->getId() ) {
			throw new InvalidArgumentException( '$parent->getId() must be the same as $current->getParentId()!' );
		}

		$change = $this->getChangeForModification( $parent->getContent(), $current->getContent() );

		if ( !$change ) {
			// nothing to do
			return null;
		}

		$change->setRevisionInfo( $current );

		wfProfileOut( __METHOD__ );
		return $change;
	}

	/**
	 * Returns a EntityChange based on the old and new content object, taking
	 * redirects into consideration.
	 *
	 * @todo: Notify the client about changes to redirects explicitly.
	 *
	 * @param EntityContent $oldContent
	 * @param EntityContent $newContent
	 *
	 * @return EntityChange|null
	 */
	private function getChangeForModification( EntityContent $oldContent, EntityContent $newContent ) {
		$oldEntity = $oldContent->isRedirect() ? null : $oldContent->getEntity();
		$newEntity = $newContent->isRedirect() ? null : $newContent->getEntity();

		if ( $oldEntity === null && $newEntity === null ) {
			// Old and new versions are redirects. Nothing to do.
			return null;
		} elseif ( $newEntity === null ) {
			// The new version is a redirect. For now, treat that as a deletion.
			$action = EntityChange::REMOVE;
		} elseif ( $oldEntity === null ) {
			// The old version is a redirect. For now, treat that like restoring the entity.
			$action = EntityChange::RESTORE;
		} else {
			// No redirects involved
			$action = EntityChange::UPDATE;
		}

		return $this->newFromUpdate( $action, $oldEntity, $newEntity );
	}

}

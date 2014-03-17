<?php

namespace Wikibase;

use MWException;
use InvalidArgumentException;
use Status;
use Title;
use User;
use WikiPage;
use Revision;

/**
 * Factory for EntityContent objects.
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityContentFactory implements EntityTitleLookup, EntityPermissionChecker {

	// TODO: inject this map and allow extensions to somehow extend it
	protected static $typeMap = array(
		Item::ENTITY_TYPE => CONTENT_MODEL_WIKIBASE_ITEM,
		Property::ENTITY_TYPE => CONTENT_MODEL_WIKIBASE_PROPERTY,
	);

	protected $contentModelIds;

	public function __construct( array $contentModelIds ) {
		$this->contentModelIds = $contentModelIds;
	}

	/**
	 * Determines whether the given content model is designated to hold some kind of Wikibase entity.
	 * Shorthand for in_array( $ns, self::getEntityModels() );
	 *
	 * @since 0.2
	 *
	 * @param String $model the content model ID
	 *
	 * @return bool True iff $model is an entity content model
	 */
	public function isEntityContentModel( $model ) {
		return in_array( $model, $this->getEntityContentModels() );
	}

	/**
	 * Returns a list of content model IDs that are used to represent Wikibase entities.
	 *
	 * @since 0.2
	 *
	 * @return array An array of string content model IDs.
	 */
	public function getEntityContentModels() {
		return $this->contentModelIds;
	}

	/**
	 * Get the items corresponding to the provided language and label pair.
	 * A description can also be provided, in which case only the item with
	 * that description will be returned (as only element in the array).
	 *
	 * @since 0.2
	 *
	 * @param string $language
	 * @param string $label
	 * @param string|null $description
	 * @param string|null $entityType
	 * @param bool $fuzzySearch if false, only exact matches are returned, otherwise more relaxed search . Defaults to false.
	 *
	 * @return EntityContent[]
	 */
	public function getFromLabel( $language, $label, $description = null, $entityType = null, $fuzzySearch = false ) {
		$entityIds = StoreFactory::getStore()->getTermIndex()->getEntityIdsForLabel( $label, $language, $description, $entityType, $fuzzySearch );
		$entities = array();

		foreach ( $entityIds as $entityId ) {
			list( $type, $id ) = $entityId;
			$entity = self::getFromId( new EntityId( $type, $id ) );

			if ( $entity !== null ) {
				$entities[] = $entity;
			}
		}

		return $entities;
	}

	/**
	 * Get the entity content for the entity with the provided id
	 * if it's available to the specified audience.
	 * If the specified audience does not have the ability to view this
	 * revision, if there is no such item, null will be returned.
	 *
	 * @since 0.2
	 *
	 * @param EntityId $id
	 *
	 * @param integer $audience: one of:
	 *      Revision::FOR_PUBLIC       to be displayed to all users
	 *      Revision::FOR_THIS_USER    to be displayed to $wgUser
	 *      Revision::RAW              get the text regardless of permissions
	 *
	 * @return EntityContent|null
	 */
	public function getFromId( EntityId $id, $audience = Revision::FOR_PUBLIC ) {
		// TODO: since we already did the trouble of getting a WikiPage here,
		// we probably want to keep a copy of it in the Content object.
		return $this->getWikiPageForId( $id )->getContent( $audience );
	}

	/**
	 * Returns the Title object for the item with provided id.
	 *
	 * @since 0.3
	 *
	 * @param EntityId $id
	 *
	 * @throws MWException
	 * @return Title
	 */
	public function getTitleForId( EntityId $id ) {
		return Title::newFromText(
			$id->getSerialization(),
			$this->getNamespaceForType( $id->getEntityType() )
		);
	}

	/**
	 * Determines what namespace is suitable for the given type of entities.
	 *
	 * @since 0.5
	 *
	 * @param int $type
	 *
	 * @return int
	 */
	public function getNamespaceForType( $type ) {
		return NamespaceUtils::getEntityNamespace( self::$typeMap[$type] );
	}

	/**
	 * Returns the WikiPage object for the item with provided id.
	 *
	 * @since 0.3
	 *
	 * @param EntityId
	 *
	 * @return WikiPage
	 */
	public function getWikiPageForId( EntityId $id ) {
		return new WikiPage( $this->getTitleForId( $id ) );
	}

	/**
	 * Get the entity content with the provided revision id, or null if there is no such entity content.
	 *
	 * Note that this returns an old content that may not be valid anymore.
	 *
	 * @since 0.2
	 *
	 * @param integer $revisionId
	 *
	 * @return EntityContent|null
	 */
	public function getFromRevision( $revisionId ) {
		$revision = \Revision::newFromId( intval( $revisionId ) );

		if ( $revision === null ) {
			return null;
		}

		return $revision->getContent();
	}

	/**
	 * Constructs a new EntityContent from an Entity.
	 *
	 * @since 0.3
	 *
	 * @param Entity $entity
	 *
	 * @return EntityContent
	 */
	public function newFromEntity( Entity $entity ) {
		/**
		 * @var EntityHandler $handler
		 */
		$handler = \ContentHandler::getForModelID( self::$typeMap[$entity->getType()] );

		return $handler->newContentFromEntity( $entity );
	}

	/**
	 * Constructs a new EntityContent from a given type.
	 *
	 * @since 0.4
	 *
	 * @param string $type
	 *
	 * @return EntityContent
	 *
	 * @throws InvalidArgumentException
	 */
	public function newFromType( $type ) {
		if ( !is_string( $type ) ) {
			throw new InvalidArgumentException( '$type needs to be a string' );
		}

		if ( $type === Item::ENTITY_TYPE ) {
			return ItemContent::newEmpty();
		} elseif ( $type === Property::ENTITY_TYPE ) {
			return PropertyContent::newEmpty();
		} else {
			throw new InvalidArgumentException( 'unknown entity type $type' );
		}
	}

	/**
	 * @see EntityPermissionChecker::getPermissionStatusForEntityId
	 *
	 * @param User $user
	 * @param string $permission
	 * @param Title $entityPage
	 * @param string $quick
	 *
	 * @return Status a status object representing the check's result.
	 */
	protected function getPermissionStatus( User $user, $permission, Title $entityPage, $quick = '' ) {
		wfProfileIn( __METHOD__ );

		$errors = $entityPage->getUserPermissionsErrors( $permission, $user, $quick !== 'quick' );
		$status = Status::newGood();

		foreach ( $errors as $error ) {
			call_user_func_array( array( $status, 'fatal'), $error );
			$status->setResult( false );
		}

		wfProfileOut( __METHOD__ );
		return $status;
	}

	/**
	 * @see EntityPermissionChecker::getPermissionStatusForEntityId
	 *
	 * @param User $user
	 * @param string $permission
	 * @param EntityId $entityId
	 * @param string $quick
	 *
	 * @return Status a status object representing the check's result.
	 */
	public function getPermissionStatusForEntityId( User $user, $permission, EntityId $entityId, $quick = '' ) {
		wfProfileIn( __METHOD__ );

		$title = $this->getTitleForId( $entityId );
		$status = $this->getPermissionStatus( $user, $permission, $title );

		wfProfileOut( __METHOD__ );
		return $status;
	}

	/**
	 * @see EntityPermissionChecker::getPermissionStatusForEntityType
	 *
	 * @param User $user
	 * @param string $permission
	 * @param string $type
	 * @param string $quick
	 *
	 * @return Status a status object representing the check's result.
	 */
	public function getPermissionStatusForEntityType( User $user, $permission, $type, $quick = '' ) {
		wfProfileIn( __METHOD__ );

		$ns = $this->getNamespaceForType( $type );
		$dummyTitle = Title::makeTitleSafe( $ns, '/' );

		$status = $this->getPermissionStatus( $user, $permission, $dummyTitle );

		wfProfileOut( __METHOD__ );
		return $status;
	}

	/**
	 * @see EntityPermissionChecker::getPermissionStatusForEntity
	 *
	 * @note When checking for the 'edit' permission, this will check the 'createpage'
	 * permission first in case the entity does not yet exist (i.e. if $entity->getId()
	 * returns null).
	 *
	 * @param User $user
	 * @param string $permission
	 * @param Entity $entity
	 * @param string $quick
	 *
	 * @return Status a status object representing the check's result.
	 */
	public function getPermissionStatusForEntity( User $user, $permission, Entity $entity, $quick = '' ) {
		$id = $entity->getId();
		$status = null;

		if ( !$id ) {
			$type = $entity->getType();

			if ( $permission === 'edit' ) {
				// for editing a non-existing page, check the createpage permission
				$status = $this->getPermissionStatusForEntityType( $user, 'createpage', $type );
			}

			if ( !$status || $status->isOK() ) {
				$status = $this->getPermissionStatusForEntityType( $user, $permission, $type );
			}
		} else {
			$status = $this->getPermissionStatusForEntityId( $user, $permission, $id );
		}

		return $status;
	}
}

<?php

namespace Wikibase;

use ContentHandler;
use MWException;
use OutOfBoundsException;
use Revision;
use Status;
use Title;
use User;

/**
 * Factory for EntityContent objects.
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class EntityContentFactory implements EntityTitleLookup, EntityPermissionChecker {

	/**
	 * @since 0.5
	 *
	 * @var array Entity type -> content model mapping
	 */
	protected $typeMap;

	/**
	 * @param array $typeMap Entity type -> content model mapping
	 */
	public function __construct( array $typeMap ) {
		$this->typeMap = $typeMap;
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
		return $this->typeMap;
	}

	/**
	 * Returns a list of content Entity type IDs used for Wikibase Entities.
	 *
	 * @since 0.5
	 *
	 * @return array An array of string content model IDs.
	 */
	public function getEntityTypes() {
		return array_flip( $this->typeMap );
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
		$handler = $this->getContentHandlerForType( $id->getEntityType() );
		return $handler->getTitleForId( $id );
	}

	/**
	 * Determines what namespace is suitable for the given type of entities.
	 *
	 * @since 0.5
	 *
	 * @param int $type
	 *
	 * @throws OutOfBoundsException if no content model is defined for the given entity type.
	 * @return int
	 */
	public function getNamespaceForType( $type ) {
		$handler = $this->getContentHandlerForType( $type );
		return $handler->getEntityNamespace();
	}

	/**
	 * Returns the ContentHandler for the given entity type.
	 *
	 * @since 0.5
	 *
	 * @param string $type
	 *
	 * @throws OutOfBoundsException if no content model is defined for the given entity type.
	 * @return EntityHandler
	 */
	public function getContentHandlerForType( $type ) {
		$model = $this->getContentModelForType( $type );
		return ContentHandler::getForModelID( $model );
	}

	/**
	 * Determines what content model is suitable for the given type of entities.
	 *
	 * @since 0.5
	 *
	 * @param string $type
	 *
	 * @throws OutOfBoundsException if no content model is defined for the given entity type.
	 * @return int
	 */
	public function getContentModelForType( $type ) {
		if ( !isset( $this->typeMap[$type] ) ) {
			throw new OutOfBoundsException( 'No content model defined for entity type ' . $type );
		}

		return $this->typeMap[$type];
	}

	/**
	 * Get the entity content with the provided revision id, or null if there is no such entity content.
	 *
	 * Note that this returns an old content that may not be valid anymore.
	 *
	 * @since 0.2
	 *
	 * @param int $revisionId
	 *
	 * @return EntityContent|null
	 */
	public function getFromRevision( $revisionId ) {
		$revision = Revision::newFromId( intval( $revisionId ) );

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
		$handler = $this->getContentHandlerForType( $entity->getType() );
		return $handler->newContentFromEntity( $entity );
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
	 *
	 * @todo Move to a separate service (merge into WikiPageEntityStore?)
	 */
	protected function getPermissionStatus( User $user, $permission, Title $entityPage, $quick = '' ) {
		wfProfileIn( __METHOD__ );

		//XXX: would be nice to be able to pass the $short flag too,
		//     as used by getUserPermissionsErrorsInternal. But Title doesn't expose that.
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
	 *
	 * @todo Move to a separate service (merge into WikiPageEntityStore?)
	 */
	public function getPermissionStatusForEntityId( User $user, $permission, EntityId $entityId, $quick = '' ) {
		wfProfileIn( __METHOD__ );

		$title = $this->getTitleForId( $entityId );
		$status = $this->getPermissionStatus( $user, $permission, $title, $quick );

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
	 *
	 * @todo Move to a separate service (merge into WikiPageEntityStore?)
	 */
	public function getPermissionStatusForEntityType( User $user, $permission, $type, $quick = '' ) {
		wfProfileIn( __METHOD__ );

		$ns = $this->getNamespaceForType( $type );
		$dummyTitle = Title::makeTitleSafe( $ns, '/' );

		$status = $this->getPermissionStatus( $user, $permission, $dummyTitle, $quick );

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
	 *
	 * @todo Move to a separate service (merge into WikiPageEntityStore?)
	 */
	public function getPermissionStatusForEntity( User $user, $permission, Entity $entity, $quick = '' ) {
		$id = $entity->getId();
		$status = null;

		if ( !$id ) {
			$type = $entity->getType();

			if ( $permission === 'edit' ) {
				// for editing a non-existing page, check the createpage permission
				$status = $this->getPermissionStatusForEntityType( $user, 'createpage', $type, $quick );
			}

			if ( !$status || $status->isOK() ) {
				$status = $this->getPermissionStatusForEntityType( $user, $permission, $type, $quick );
			}
		} else {
			$status = $this->getPermissionStatusForEntityId( $user, $permission, $id, $quick );
		}

		return $status;
	}

}

<?php

namespace Wikibase\Repo\Content;

use ContentHandler;
use MWException;
use OutOfBoundsException;
use Revision;
use Status;
use Title;
use User;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityContent;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Store\EntityPermissionChecker;

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
	 * @var string[] Entity type ID to content model ID mapping.
	 */
	private $entityContentModels;

	/**
	 * @param string[] $entityContentModels Entity type ID to content model ID mapping.
	 */
	public function __construct( array $entityContentModels ) {
		$this->entityContentModels = $entityContentModels;
	}

	/**
	 * Determines whether the given content model is designated to hold some kind of Wikibase entity.
	 *
	 * @since 0.2
	 *
	 * @param string $contentModel
	 *
	 * @return bool If the given content model ID is a known entity content model.
	 */
	public function isEntityContentModel( $contentModel ) {
		return in_array( $contentModel, $this->entityContentModels );
	}

	/**
	 * @since 0.2
	 *
	 * @return string[] A list of content model IDs used to represent Wikibase entities.
	 */
	public function getEntityContentModels() {
		return array_values( $this->entityContentModels );
	}

	/**
	 * @since 0.5
	 *
	 * @return string[] A list of entity type IDs used for Wikibase entities.
	 */
	public function getEntityTypes() {
		return array_keys( $this->entityContentModels );
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
	 * @param string $entityType
	 *
	 * @throws OutOfBoundsException if no content model is defined for the given entity type.
	 * @return int
	 */
	public function getNamespaceForType( $entityType ) {
		$handler = $this->getContentHandlerForType( $entityType );
		return $handler->getEntityNamespace();
	}

	/**
	 * Returns the ContentHandler for the given entity type.
	 *
	 * @since 0.5
	 *
	 * @param string $entityType
	 *
	 * @throws OutOfBoundsException if no content model is defined for the given entity type.
	 * @return EntityHandler
	 */
	public function getContentHandlerForType( $entityType ) {
		$contentModel = $this->getContentModelForType( $entityType );
		return ContentHandler::getForModelID( $contentModel );
	}

	/**
	 * Determines what content model is suitable for the given type of entities.
	 *
	 * @since 0.5
	 *
	 * @param string $entityType
	 *
	 * @throws OutOfBoundsException if no content model is defined for the given entity type.
	 * @return int
	 */
	public function getContentModelForType( $entityType ) {
		if ( !isset( $this->entityContentModels[$entityType] ) ) {
			throw new OutOfBoundsException( 'No content model defined for entity type ' . $entityType );
		}

		return $this->entityContentModels[$entityType];
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
	 * @see EntityHandler::makeEntityContent
	 *
	 * @since 0.3
	 *
	 * @param Entity $entity
	 *
	 * @return EntityContent
	 */
	public function newFromEntity( Entity $entity ) {
		$handler = $this->getContentHandlerForType( $entity->getType() );
		return $handler->makeEntityContent( $entity );
	}

	/**
	 * Constructs a new EntityContent from an EntityRedirect,
	 * or null if the respective kind of entity does not support redirects.
	 *
	 * @see EntityHandler::makeEntityRedirectContent
	 *
	 * @since 0.5
	 *
	 * @param EntityRedirect $redirect
	 *
	 * @return EntityContent|null
	 */
	public function newFromRedirect( EntityRedirect $redirect ) {
		$handler = $this->getContentHandlerForType( $redirect->getEntityId()->getEntityType() );
		return $handler->makeEntityRedirectContent( $redirect );
	}

	/**
	 * @param User $user
	 * @param string $permission
	 * @param Title $entityPage
	 * @param string $quick
	 *
	 * @return string[]
	 */
	protected function getPermissionErrors( User $user, $permission, Title $entityPage, $quick = '' ) {
		//XXX: would be nice to be able to pass the $short flag too,
		//     as used by getUserPermissionsErrorsInternal. But Title doesn't expose that.
		return $entityPage->getUserPermissionsErrors( $permission, $user, $quick !== 'quick' );
	}

	/**
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
		$errors = $this->getPermissionErrors( $user, $permission, $entityPage, $quick );
		$status = $this->getStatusForPermissionErrors( $errors );

		wfProfileOut( __METHOD__ );
		return $status;
	}

	/**
	 * @param string[] $errors
	 *
	 * @return Status
	 */
	protected function getStatusForPermissionErrors( array $errors ) {
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
	 * @param string $entityType
	 * @param string $quick
	 *
	 * @return Status a status object representing the check's result.
	 *
	 * @todo Move to a separate service (merge into WikiPageEntityStore?)
	 */
	public function getPermissionStatusForEntityType( User $user, $permission, $entityType, $quick = '' ) {
		wfProfileIn( __METHOD__ );

		$ns = $this->getNamespaceForType( $entityType );
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
	 c*
	 * @todo Move to a separate service (merge into WikiPageEntityStore?)
	 */
	public function getPermissionStatusForEntity( User $user, $permission, Entity $entity, $quick = '' ) {
		$id = $entity->getId();
		$status = null;

		if ( !$id ) {
			$entityType = $entity->getType();

			if ( $permission === 'edit' ) {
				// for editing a non-existing page, check the createpage permission
				$status = $this->getPermissionStatusForEntityType( $user, 'createpage', $entityType, $quick );
			}

			if ( !$status || $status->isOK() ) {
				$status = $this->getPermissionStatusForEntityType( $user, $permission, $entityType, $quick );
			}
		} else {
			$status = $this->getPermissionStatusForEntityId( $user, $permission, $id, $quick );
		}

		return $status;
	}

	/**
	 * @param Title $title
	 * @param User $user
	 * @param string $permission
	 *
	 * @return Status
	 */
	public function getPermissionForTitle( Title $title, EntityContent $content, User $user, $permission ) {
		$entityContentTitle = $this->getTitleForId( $content->getEntity()->getId() );

		if ( $entityContentTitle->getFullText() !== $title->getFullText() ) {
			throw new MWException( '$title does not match content' );
		}

		$errors = $title->getUserPermissionsErrors( $permission, $user, 'quick' );
		return $this->getStatusForPermissionErrors( $errors );
	}

}

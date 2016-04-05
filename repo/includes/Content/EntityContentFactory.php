<?php

namespace Wikibase\Repo\Content;

use InvalidArgumentException;
use MWException;
use OutOfBoundsException;
use Revision;
use Status;
use Title;
use User;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\EntityContent;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Store\EntityIdLookup;
use Wikimedia\Assert\Assert;

/**
 * Factory for EntityContent objects.
 *
 * @since 0.2
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityContentFactory implements EntityTitleLookup, EntityIdLookup, EntityPermissionChecker {

	/**
	 * @var string[] Entity type ID to content model ID mapping.
	 */
	private $entityContentModels;

	/**
	 * @var callable[] Entity type ID to callback mapping for creating ContentHandler objects.
	 */
	private $entityHandlerFactoryCallbacks;

	/**
	 * @var EntityHandler[] Entity type ID to entity handler mapping.
	 */
	private $entityHandlers = [];

	/**
	 * @param string[] $entityContentModels Entity type ID to content model ID mapping.
	 * @param callable[] $entityHandlerFactoryCallbacks Entity type ID to callback mapping for
	 *  creating ContentHandler objects.
	 */
	public function __construct( array $entityContentModels, array $entityHandlerFactoryCallbacks ) {
		Assert::parameterElementType( 'string', $entityContentModels, '$entityContentModels' );
		Assert::parameterElementType( 'callable', $entityHandlerFactoryCallbacks, '$entityHandlerFactoryCallbacks' );

		$this->entityContentModels = $entityContentModels;
		$this->entityHandlerFactoryCallbacks = $entityHandlerFactoryCallbacks;
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
	 * @throws OutOfBoundsException
	 * @throws InvalidArgumentException
	 * @return Title
	 */
	public function getTitleForId( EntityId $id ) {
		$handler = $this->getContentHandlerForType( $id->getEntityType() );
		return $handler->getTitleForId( $id );
	}

	/**
	 * Returns the ID of the entity associated with the given page title.
	 *
	 * @note There is no guarantee that the EntityId returned by this method refers to
	 * an existing entity.
	 *
	 * @param Title $title
	 *
	 * @return EntityId|null
	 */
	public function getEntityIdForTitle( Title $title ) {
		$contentModel = $title->getContentModel();

		try {
			$handler = $this->getEntityHandlerForContentModel( $contentModel );
			return $handler->getIdForTitle( $title );
		} catch ( OutOfBoundsException $ex ) {
			// Not an entity content model
		} catch ( EntityIdParsingException $ex ) {
			// Not a valid entity page title.
		}

		return null;
	}

	/**
	 * @see EntityIdLookup::getEntityIds
	 *
	 * @note: the current implementation skips non-existing entities, but there is no guarantee
	 * that this will always be the case.
	 *
	 * @param Title[] $titles
	 *
	 * @throws StorageException
	 * @return EntityId[] Entity IDs, keyed by page IDs.
	 */
	public function getEntityIds( array $titles ) {
		$entityIds = [];

		foreach ( $titles as $title ) {
			$pageId = $title->getArticleID();

			if ( $pageId > 0 ) {
				$entityId = $this->getEntityIdForTitle( $title );

				if ( $entityId !== null ) {
					$entityIds[$pageId] = $entityId;
				}
			}
		}

		return $entityIds;
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
	 * Returns the EntityHandler for the given entity type.
	 *
	 * @since 0.5
	 *
	 * @param string $entityType
	 *
	 * @throws OutOfBoundsException if no content model is defined for the given entity type.
	 * @return EntityHandler
	 */
	public function getContentHandlerForType( $entityType ) {
		if ( !isset( $this->entityHandlerFactoryCallbacks[$entityType] ) ) {
			throw new OutOfBoundsException( 'No content handler defined for entity type ' . $entityType );
		}

		if ( !isset( $this->entityHandlers[$entityType] ) ) {
			$entityHandler = call_user_func( $this->entityHandlerFactoryCallbacks[$entityType] );

			Assert::postcondition(
				$entityHandler instanceof EntityHandler,
				'Callback must return an instance of EntityHandler'
			);

			$this->entityHandlers[$entityType] = $entityHandler;
		}

		return $this->entityHandlers[$entityType];
	}

	/**
	 * Returns the EntityHandler for the given model id.
	 *
	 * @param string $contentModel
	 *
	 * @throws OutOfBoundsException if no entity handler is defined for the given content model.
	 * @return EntityHandler
	 */
	public function getEntityHandlerForContentModel( $contentModel ) {
		$entityTypePerModel = array_flip( $this->entityContentModels );

		if ( !isset( $entityTypePerModel[$contentModel] ) ) {
			throw new OutOfBoundsException( 'No entity handler defined for content model ' . $contentModel );
		}

		return $this->getContentHandlerForType( $entityTypePerModel[$contentModel] );
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
		$revision = Revision::newFromId( (int)$revisionId );

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
	 * @param EntityDocument $entity
	 *
	 * @return EntityContent
	 */
	public function newFromEntity( EntityDocument $entity ) {
		$handler = $this->getContentHandlerForType( $entity->getType() );
		return $handler->makeEntityContent( new EntityInstanceHolder( $entity ) );
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
	 * //XXX: would be nice to be able to pass the $short flag too,
	 *        as used by getUserPermissionsErrorsInternal. But Title doesn't expose that.
	 * @todo Move to a separate service (merge into WikiPageEntityStore?)
	 *
	 * @return Status a status object representing the check's result.
	 */
	protected function getPermissionStatus( User $user, $permission, Title $entityPage, $quick = '' ) {
		$errors = $entityPage->getUserPermissionsErrors( $permission, $user, $quick !== 'quick' );
		return $this->getStatusForPermissionErrors( $errors );
	}

	/**
	 * @param string[] $errors
	 *
	 * @return Status
	 */
	protected function getStatusForPermissionErrors( array $errors ) {
		$status = Status::newGood();

		foreach ( $errors as $error ) {
			call_user_func_array( array( $status, 'fatal' ), $error );
			$status->setResult( false );
		}

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
		$title = $this->getTitleForId( $entityId );
		return $this->getPermissionStatus( $user, $permission, $title, $quick );
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
		$ns = $this->getNamespaceForType( $entityType );
		$dummyTitle = Title::makeTitleSafe( $ns, '/' );

		return $this->getPermissionStatus( $user, $permission, $dummyTitle, $quick );
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
	 * @param EntityDocument $entity
	 * @param string $quick
	 *
	 * @return Status a status object representing the check's result.
	 *
	 * @todo Move to a separate service (merge into WikiPageEntityStore?)
	 */
	public function getPermissionStatusForEntity( User $user, $permission, EntityDocument $entity, $quick = '' ) {
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

}

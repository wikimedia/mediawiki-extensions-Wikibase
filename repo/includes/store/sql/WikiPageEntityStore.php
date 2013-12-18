<?php

namespace Wikibase;

use PermissionsError;
use Revision;
use User;

/**
 * EntityStore implementation based on WikiPage.
 *
 * @todo: move the actual implementation of the storage logic from EntityContent into this class.
 *
 * @since 0.5
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class WikiPageEntityStore implements EntityStore {

	/**
	 * @var EntityContentFactory
	 */
	protected $contentFactory;

	/**
	 * @var EntityCache
	 */
	protected $entityLookup;

	/**
	 * @param EntityRevisionLookup $entityLookup for looking up entities. If this implements
	 *        EntityCache, cached entities will automatically be updated by saveEntity.
	 *
	 * @param EntityContentFactory $contentFactory
	 */
	public function __construct( EntityRevisionLookup $entityLookup, EntityContentFactory $contentFactory ) {
		$this->entityLookup = $entityLookup;
		$this->contentFactory = $contentFactory;
	}

	/**
	 * Saves the given Entity to a wiki page via a WikiPage object.
	 *
	 * @param Entity $entity the entity to save.
	 * @param string $summary the edit summary for the new revision.
	 * @param User $user the user to whom to attribute the edit
	 * @param int $flags EDIT_XXX flags, as defined for WikiPage::doEditContent.
	 * @param int|bool $baseRevId the revision ID $entity is based on. Saving will
	 * fail if $baseRevId is not the current revision ID.
	 *
	 * @see EntityStore::saveEntity()
	 * @see WikiPage::doEditContent
	 *
	 * @return EntityRevision
	 *
	 * @throws StorageException
	 * @throws PermissionsError
	 */
	public function saveEntity( Entity $entity, $summary, User $user, $flags = 0, $baseRevId = false ) {
		$content = $this->contentFactory->newFromEntity( $entity );

		//TODO: move the logic from EntityContent::save here!
		$status = $content->save( $summary, $user, $flags, $baseRevId );

		if ( !$status->isOK() ) {
			$messageKeys = array_map( function( array $error ) {
				return $error[0];
			}, $status->getErrorsArray() );

			//TODO: nicer error! Can we keep the status somehow? Can we make an ErrorPageError sensibly?
			throw new StorageException( implode( ', ', $messageKeys ) );
		}

		// as per convention defined by WikiPage, the new revision ID is in the status value:
		$value = $status->getValue();

		/* @var Revision $revision */
		$revision = isset( $value['revision'] ) ? $value['revision'] : null;

		$rev = new EntityRevision( $entity, $revision->getId(), $revision->getTimestamp() );

		if ( $this->entityLookup instanceof EntityCache ) {
			$this->entityLookup->updateCachedEntity( $rev );
		}

		return $rev;
	}


	/**
	 * @see EntityLookup::getEntity()
	 *
	 * @param EntityID $entityId
	 * @param int $revision The desired revision id, 0 means "current".
	 *
	 * @return Entity|null
	 *
	 * @throw StorageException
	 */
	public function getEntity( EntityID $entityId, $revision = 0 ) {
		return $this->entityLookup->getEntity( $entityId, $revision );
	}

	/**
	 * @see EntityLookup::hasEntity()
	 *
	 * @param EntityID $entityId
	 *
	 * @return bool
	 */
	public function hasEntity( EntityID $entityId ) {
		return $this->entityLookup->hasEntity( $entityId );
	}

	/**
	 * @see EntityLookup::getEntities()
	 *
	 * @param EntityID[] $entityIds
	 *
	 * @return Entity|null[]
	 */
	public function getEntities( array $entityIds ) {
		return $this->entityLookup->getEntities( $entityIds );
	}

	/**
	 * @see EntityRevisionLookup::getEntityRevision()
	 *
	 * @param EntityID $entityId
	 * @param int $revision The desired revision id, 0 means "current".
	 *
	 * @return EntityRevision|null
	 * @throw StorageException
	 */
	public function getEntityRevision( EntityID $entityId, $revision = 0 ) {
		return $this->entityLookup->getEntityRevision( $entityId, $revision );
	}
}
 
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
	 * @param EntityContentFactory $contentFactory
	 */
	public function __construct( EntityContentFactory $contentFactory ) {
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

		return new EntityRevision( $entity, $revision->getId(), $revision->getTimestamp() );
	}
}
 
<?php

namespace Wikibase\Lib\Store\Sql;

use DBAccessBase;
use InvalidArgumentException;
use MediaWiki\Storage\RevisionAccessException;
use MediaWiki\Storage\RevisionStore;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\EntityContent;
use Wikibase\Lib\Store\BadRevisionException;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\StorageException;
use Wikimedia\Assert\Assert;

/**
 * Implements an entity repo based on blobs stored in wiki pages on a locally reachable
 * database server. This class also supports memcached (or accelerator) based caching
 * of entities.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class WikiPageEntityRevisionLookup extends DBAccessBase implements EntityRevisionLookup {

	/**
	 * @var EntityContentDataCodec
	 */
	private $contentCodec;

	/**
	 * @deprecated
	 * @var WikiPageEntityMetaDataAccessor
	 */
	private $entityMetaDataAccessor;

	/**
	 * @var RevisionStore
	 */
	private $revisionStore;

	/**
	 * @var string
	 */
	private $repositoryName;

	/**
	 * @param EntityContentDataCodec $contentCodec
	 * @param WikiPageEntityMetaDataAccessor $entityMetaDataAccessor
	 * @param RevisionStore $revisionStore
	 * @param string $repositoryName The name of the repository to lookup from (use an empty string for the local repository)
	 * @param string|bool $wiki The name of the wiki database to use (use false for the local wiki)
	 */
	public function __construct(
		EntityContentDataCodec $contentCodec,
		WikiPageEntityMetaDataAccessor $entityMetaDataAccessor,
		RevisionStore $revisionStore,
		$repositoryName = '',
		$wiki = false
	) {
		parent::__construct( $wiki );

		$this->contentCodec = $contentCodec;

		$this->entityMetaDataAccessor = $entityMetaDataAccessor;
		$this->revisionStore = $revisionStore;
		$this->repositoryName = $repositoryName;
	}

	/**
	 * @see   EntityRevisionLookup::getEntityRevision
	 *
	 * @param EntityId $entityId
	 * @param int $revisionId The desired revision id, or 0 for the latest revision.
	 * @param string $mode LATEST_FROM_REPLICA, LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *        LATEST_FROM_MASTER.
	 *
	 * @throws RevisionedUnresolvedRedirectException
	 * @throws StorageException
	 * @return EntityRevision|null
	 */
	public function getEntityRevision(
		EntityId $entityId,
		$revisionId = 0,
		$mode = self::LATEST_FROM_REPLICA
	) {
		Assert::parameterType( 'integer', $revisionId, '$revisionId' );
		Assert::parameterType( 'string', $mode, '$mode' );
		$this->assertEntityIdFromRightRepository( $entityId );

		wfDebugLog( __CLASS__, __FUNCTION__ . ': Looking up entity ' . $entityId
			. " (revision $revisionId)." );

		/** @var EntityRevision $entityRevision */
		$entityRevision = null;

		/** @var EntityRedirect $redirect */
		list( $entityRevision, $redirect ) = $this->loadEntity( $revisionId );

		if ( $redirect !== null ) {
			throw new RevisionedUnresolvedRedirectException(
				$entityId,
				$redirect->getTargetId(),
				$revisionId
			);
		}

		if ( $entityRevision === null ) {
			// This happens when there is a problem with the external store or if access is forbidden
			wfLogWarning( __METHOD__ . ': Entity not loaded for ' . $entityId );
		}

		if ( $entityRevision !== null && !$entityRevision->getEntity()->getId()->equals( $entityId ) ) {
			// This can happen when giving a revision ID that doesn't belong to the given entity
			$actualEntityId = $entityRevision->getEntity()->getId()->getSerialization();

			// Get the revision id we actually loaded, if none was passed explicitly
			$revisionId = $revisionId ?: $entityRevision->getRevisionId();
			throw new BadRevisionException( "Revision $revisionId belongs to $actualEntityId instead of expected $entityId" );
		}

		if ( $revisionId > 0 && $entityRevision === null ) {
			// If a revision ID was specified, but that revision doesn't exist:
			throw new BadRevisionException( "No such revision found for $entityId: $revisionId" );
		}

		return $entityRevision;
	}

	/**
	 * @see EntityRevisionLookup::getLatestRevisionId
	 *
	 * @param EntityId $entityId
	 * @param string $mode
	 *
	 * @return int|false
	 */
	public function getLatestRevisionId( EntityId $entityId, $mode = self::LATEST_FROM_REPLICA ) {
		$this->assertEntityIdFromRightRepository( $entityId );

		// TODO stop using entityMetaDataAccessor
		$rows = $this->entityMetaDataAccessor->loadRevisionInformation( [ $entityId ], $mode );
		$row = $rows[$entityId->getSerialization()];

		if ( $row && $row->page_latest && !$row->page_is_redirect ) {
			return (int)$row->page_latest;
		}

		return false;
	}

	/**
	 * @param int $revisionId
	 *
	 * @throws StorageException
	 * @return object[] list( EntityRevision|null $entityRevision, EntityRedirect|null $entityRedirect )
	 * with either $entityRevision or $entityRedirect or both being null (but not both being non-null).
	 */
	private function loadEntity( $revisionId ) {
		$revision = $this->revisionStore->getRevisionById( $revisionId );

		try {
			// TODO The slot to load the entity from should be configurable
			/** @var EntityContent $content */
			$content = $revision->getContent( 'main' );
		} catch ( RevisionAccessException $e ) {
			throw new StorageException( 'getContent failed', 0, $e );
		}

		// getContent returned null, meaning access if forbidden.
		if ( $content === null ) {
			// WARNING: This will make it look like suppressed revisions don't exist at all.
			// Wikibase should handle old revisions with suppressed content gracefully.
			// @see https://phabricator.wikimedia.org/T198467
			return [ null, null ];
		}

		if ( !$content->isRedirect() ) {
			return [ new EntityRevision( $content->getEntity(), $revision->getId(), $revision->getTimestamp() ), null ];
		} else {
			return [ null, $content->getEntityRedirect() ];
		}
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws InvalidArgumentException When $entityId does not belong the repository of this lookup
	 */
	private function assertEntityIdFromRightRepository( EntityId $entityId ) {
		if ( !$this->isEntityIdFromRightRepository( $entityId ) ) {
			throw new InvalidArgumentException(
				'Could not load data from the database of repository: ' .
				$entityId->getRepositoryName()
			);
		}
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return bool
	 */
	private function isEntityIdFromRightRepository( EntityId $entityId ) {
		return $entityId->getRepositoryName() === $this->repositoryName;
	}

}

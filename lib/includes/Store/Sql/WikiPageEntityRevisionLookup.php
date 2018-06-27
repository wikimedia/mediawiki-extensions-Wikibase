<?php

namespace Wikibase\Lib\Store\Sql;

use DBAccessBase;
use MediaWiki\Storage\RevisionStore;
use MWContentSerializationException;
use MWException;
use stdClass;
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
	 * @var WikiPageEntityMetaDataAccessor
	 */
	private $entityMetaDataAccessor;

	/**
	 * @var RevisionStore
	 */
	private $revisionStore;

	/**
	 * @param EntityContentDataCodec $contentCodec
	 * @param WikiPageEntityMetaDataAccessor $entityMetaDataAccessor
	 * @param RevisionStore $revisionStore
	 * @param string|bool $wiki The name of the wiki database to use (use false for the local wiki)
	 */
	public function __construct(
		EntityContentDataCodec $contentCodec,
		WikiPageEntityMetaDataAccessor $entityMetaDataAccessor,
		RevisionStore $revisionStore,
		$wiki = false
	) {
		parent::__construct( $wiki );

		$this->contentCodec = $contentCodec;

		$this->entityMetaDataAccessor = $entityMetaDataAccessor;
		$this->revisionStore = $revisionStore;
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

		wfDebugLog( __CLASS__, __FUNCTION__ . ': Looking up entity ' . $entityId
			. " (revision $revisionId)." );

		/** @var EntityRevision $entityRevision */
		$entityRevision = null;

		if ( $revisionId > 0 ) {
			$row = $this->entityMetaDataAccessor->loadRevisionInformationByRevisionId( $entityId, $revisionId, $mode );
		} else {
			$rows = $this->entityMetaDataAccessor->loadRevisionInformation( [ $entityId ], $mode );
			$row = $rows[$entityId->getSerialization()];
		}

		if ( $row ) {
			/** @var EntityRedirect $redirect */
			try {
				list( $entityRevision, $redirect ) = $this->loadEntity( $row );
			} catch ( MWContentSerializationException $ex ) {
				throw new StorageException( 'Failed to unserialize the content object.', 0, $ex );
			}

			if ( $redirect !== null ) {
				throw new RevisionedUnresolvedRedirectException(
					$entityId,
					$redirect->getTargetId(),
					(int)$row->rev_id,
					$row->rev_timestamp
				);
			}

			if ( $entityRevision === null ) {
				// This only happens when there is a problem with the external store.
				wfLogWarning( __METHOD__ . ': Entity not loaded for ' . $entityId );
			}
		}

		if ( $entityRevision !== null && !$entityRevision->getEntity()->getId()->equals( $entityId ) ) {
			// This can happen when giving a revision ID that doesn't belong to the given entity,
			// or some meta data is incorrect.
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
		$rows = $this->entityMetaDataAccessor->loadRevisionInformation( [ $entityId ], $mode );
		$row = $rows[$entityId->getSerialization()];

		if ( $row && $row->page_latest && !$row->page_is_redirect ) {
			return (int)$row->page_latest;
		}

		return false;
	}

	/**
	 * Construct an EntityRevision object from a database row from the revision and text tables.
	 *
	 * @param stdClass $row a row object as expected Revision::getRevisionText(). That is, it
	 *        should contain the relevant fields from the revision and/or text table.
	 *
	 * @throws MWContentSerializationException
	 * @return object[] list( EntityRevision|null $entityRevision, EntityRedirect|null $entityRedirect )
	 * with either $entityRevision or $entityRedirect or both being null (but not both being non-null).
	 */
	private function loadEntity( $row ) {
		$revision = $this->revisionStore->getRevisionById( $row->rev_id );
		// TODO this should not always be the main slot
		/** @var EntityContent $content */
		$content = $revision->getContent( 'main' );

		if ( !$content->isRedirect() ) {
			return [ new EntityRevision( $content->getEntity(), $revision->getId(), $revision->getTimestamp() ), null ];
		} else {
			return [ null, $content->getEntityRedirect() ];
		}
	}

}

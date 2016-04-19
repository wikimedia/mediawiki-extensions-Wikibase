<?php

namespace Wikibase\Lib\Store;

use DBAccessBase;
use MWContentSerializationException;
use Revision;
use stdClass;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;
use Wikibase\EntityRevision;

/**
 * Implements an entity repo based on blobs stored in wiki pages on a locally reachable
 * database server. This class also supports memcached (or accelerator) based caching
 * of entities.
 *
 * @since 0.3
 *
 * @license GPL-2.0+
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
	 * @param EntityContentDataCodec $contentCodec
	 * @param WikiPageEntityMetaDataAccessor $entityMetaDataAccessor
	 * @param string|bool $wiki The name of the wiki database to use (use false for the local wiki)
	 */
	public function __construct(
		EntityContentDataCodec $contentCodec,
		WikiPageEntityMetaDataAccessor $entityMetaDataAccessor,
		$wiki = false
	) {
		parent::__construct( $wiki );

		$this->contentCodec = $contentCodec;

		$this->entityMetaDataAccessor = $entityMetaDataAccessor;
	}

	/**
	 * @since 0.4
	 * @see   EntityRevisionLookup::getEntityRevision
	 *
	 * @param EntityId $entityId
	 * @param int|string $revisionId The desired revision id, or LATEST_FROM_SLAVE or LATEST_FROM_MASTER.
	 *
	 * @throws RevisionedUnresolvedRedirectException
	 * @throws StorageException
	 * @return EntityRevision|null
	 */
	public function getEntityRevision( EntityId $entityId, $revisionId = self::LATEST_FROM_SLAVE ) {
		wfDebugLog( __CLASS__, __FUNCTION__ . ': Looking up entity ' . $entityId
			. " (revision $revisionId)." );

		// default changed from false to 0 and then to LATEST_FROM_SLAVE
		if ( $revisionId === false || $revisionId === 0 ) {
			wfWarn( 'getEntityRevision() called with $revisionId = false or 0, ' .
				'use EntityRevisionLookup::LATEST_FROM_SLAVE or EntityRevisionLookup::LATEST_FROM_MASTER instead.' );
			$revisionId = self::LATEST_FROM_SLAVE;
		}

		/** @var EntityRevision $entityRevision */
		$entityRevision = null;

		if ( is_int( $revisionId ) ) {
			$row = $this->entityMetaDataAccessor->loadRevisionInformationByRevisionId( $entityId, $revisionId );
		} else {
			$rows = $this->entityMetaDataAccessor->loadRevisionInformation( array( $entityId ), $revisionId );
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
			$revisionId = is_int( $revisionId ) ? $revisionId : $entityRevision->getRevisionId();
			throw new BadRevisionException( "Revision $revisionId belongs to $actualEntityId instead of expected $entityId" );
		}

		if ( is_int( $revisionId ) && $entityRevision === null ) {
			// If a revision ID was specified, but that revision doesn't exist:
			throw new BadRevisionException( "No such revision found for $entityId: $revisionId" );
		}

		return $entityRevision;
	}

	/**
	 * @see EntityRevisionLookup::getLatestRevisionId
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId
	 * @param string $mode
	 *
	 * @return int|false
	 */
	public function getLatestRevisionId( EntityId $entityId, $mode = self::LATEST_FROM_SLAVE ) {
		$rows = $this->entityMetaDataAccessor->loadRevisionInformation( array( $entityId ), $mode );
		$row = $rows[$entityId->getSerialization()];

		if ( $row && $row->page_latest && !$row->page_is_redirect ) {
			return (int)$row->page_latest;
		}

		return false;
	}

	/**
	 * Construct an EntityRevision object from a database row from the revision and text tables.
	 *
	 * @see loadEntityBlob()
	 *
	 * @param stdClass $row a row object as expected Revision::getRevisionText(). That is, it
	 *        should contain the relevant fields from the revision and/or text table.
	 *
	 * @throws MWContentSerializationException
	 * @return object[] list( EntityRevision|null $entityRevision, EntityRedirect|null $entityRedirect )
	 * with either $entityRevision or $entityRedirect or both being null (but not both being non-null).
	 */
	private function loadEntity( $row ) {
		$blob = $this->loadEntityBlob( $row );
		$entity = $this->contentCodec->decodeEntity( $blob, $row->rev_content_format );

		if ( $entity ) {
			$entityRevision = new EntityRevision( $entity, (int)$row->rev_id, $row->rev_timestamp );

			$result = array( $entityRevision, null );
		} else {
			$redirect = $this->contentCodec->decodeRedirect( $blob, $row->rev_content_format );

			if ( !$redirect ) {
				throw new MWContentSerializationException(
					'The serialized data contains neither an Entity nor an EntityRedirect!'
				);
			}

			$result = array( null, $redirect );
		}

		return $result;
	}

	/**
	 * Loads a blob based on a database row from the revision and text tables.
	 *
	 * This calls Revision::getRevisionText to resolve any additional indirections in getting
	 * to the actual blob data, like the "External Store" mechanism used by Wikipedia & co.
	 *
	 * @param stdClass $row a row object as expected Revision::getRevisionText(). That is, it
	 *        should contain the relevant fields from the revision and/or text table.
	 *
	 * @throws MWContentSerializationException
	 * @return string The blob
	 */
	private function loadEntityBlob( $row ) {
		wfDebugLog( __CLASS__, __FUNCTION__ . ': Calling getRevisionText() on revision '
			. $row->rev_id );

		//NOTE: $row contains revision fields from another wiki. This SHOULD not
		//      cause any problems, since getRevisionText should only look at the old_flags
		//      and old_text fields. But be aware.
		$blob = Revision::getRevisionText( $row, 'old_', $this->wiki );

		if ( $blob === false ) {
			wfWarn( 'Unable to load raw content blob for revision ' . $row->rev_id );

			throw new MWContentSerializationException(
				'Unable to load raw content blob for revision ' . $row->rev_id
			);
		}

		return $blob;
	}

}

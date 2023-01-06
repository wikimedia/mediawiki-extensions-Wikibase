<?php

namespace Wikibase\Lib\Store\Sql;

use IDBAccessObject;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\Revision\SlotRecord;
use MWContentSerializationException;
use Psr\Log\LoggerInterface;
use stdClass;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Lib\Store\BadRevisionException;
use Wikibase\Lib\Store\DivergingEntityIdException;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\InconsistentRedirectException;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
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
class WikiPageEntityRevisionLookup implements EntityRevisionLookup {

	/**
	 * @var WikiPageEntityMetaDataAccessor
	 */
	private $entityMetaDataAccessor;

	/**
	 * @var RevisionStore
	 */
	private $revisionStore;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var WikiPageEntityDataLoader
	 */
	private $entityDataLoader;

	public function __construct(
		WikiPageEntityMetaDataAccessor $entityMetaDataAccessor,
		WikiPageEntityDataLoader $entityDataLoader,
		RevisionStore $revisionStore
	) {
		$this->entityMetaDataAccessor = $entityMetaDataAccessor;
		$this->revisionStore = $revisionStore;

		$this->entityDataLoader = $entityDataLoader;

		// TODO: Inject
		$this->logger = LoggerFactory::getInstance( 'Wikibase' );
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
		$mode = LookupConstants::LATEST_FROM_REPLICA
	) {
		Assert::parameterType( 'integer', $revisionId, '$revisionId' );
		Assert::parameterType( 'string', $mode, '$mode' );

		$this->logger->debug(
			'{method}: Looking up entity {entityId} (revision {revisionId}).',
			[
				'method' => __METHOD__,
				'entityId' => $entityId,
				'revisionId' => $revisionId,
			]
		);

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
				list( $entityRevision, $redirect ) = $this->loadEntity( $row, $mode );
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
				// This may happen when there is a problem with the external store or if access is forbidden.
				// It should not happen when a revision has no entity slot –
				// in that case, the EntityMetaDataAccessor should not return a row at all.
				$this->logger->warning(
					__METHOD__ . ': Entity not loaded for {entityId}',
					[ 'entityId' => $entityId, 'revisionId' => $revisionId ]
				);
			}
		}

		if ( $entityRevision !== null && !$entityRevision->getEntity()->getId()->equals( $entityId ) ) {
			// This can happen when giving a revision ID that doesn't belong to the given entity,
			// or some meta data is incorrect.
			$actualEntityId = $entityRevision->getEntity()->getId()->getSerialization();

			// Get the revision id we actually loaded, if none was passed explicitly
			$revisionId = $revisionId ?: $entityRevision->getRevisionId();
			throw new DivergingEntityIdException(
				$revisionId,
				$entityRevision,
				$entityId->getSerialization()
			);
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
	 * This tries to provide redirect information when returning the LatestRevisionIdResult which results in loading the whole entity.
	 * This is probably okay as redirects are not the most commons case.
	 * There is no guarantee that the entity redirected to is not also a redirect.
	 *
	 * @param EntityId $entityId
	 * @param string $mode
	 *
	 * @throws InconsistentRedirectException
	 * @return LatestRevisionIdResult
	 */
	public function getLatestRevisionId( EntityId $entityId, $mode = LookupConstants::LATEST_FROM_REPLICA ) {
		$rows = $this->entityMetaDataAccessor->loadRevisionInformation( [ $entityId ], $mode );
		$row = $rows[$entityId->getSerialization()];

		if ( $row && $row->page_latest ) {
			if ( $row->page_is_redirect ) {
				/** @var EntityRedirect $redirect */
				list( , $redirect ) = $this->loadEntity( $row, $mode );
				if ( $redirect === null ) {
					$revisionId = $row->rev_id;
					$slot = $row->role_name ?? SlotRecord::MAIN;

					throw new InconsistentRedirectException(
						$revisionId,
						$slot,
						"Revision '$revisionId' is marked as revision of page redirecting to another," .
						" but no redirect entity data found in slot '$slot'."
					);
				}

				return LatestRevisionIdResult::redirect(
					(int)$row->page_latest,
					$redirect->getTargetId()
				);
			}
			return LatestRevisionIdResult::concreteRevision( (int)$row->page_latest, $row->rev_timestamp );
		}

		return LatestRevisionIdResult::nonexistentEntity();
	}

	/**
	 * Construct an EntityRevision object from a database row from the revision table.
	 *
	 * @param stdClass $row a row object as returned by WikiPageEntityMetaDataLookup.
	 * @param string $mode LATEST_FROM_REPLICA, LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *        LATEST_FROM_MASTER.
	 *
	 * @throws StorageException
	 * @return array list( EntityRevision|null $entityRevision, EntityRedirect|null $entityRedirect )
	 * with either $entityRevision or $entityRedirect or both being null (but not both being non-null).
	 */
	private function loadEntity( $row, $mode = LookupConstants::LATEST_FROM_REPLICA ) {
		$revStoreFlags = ( $mode == LookupConstants::LATEST_FROM_MASTER || $mode == LookupConstants::LATEST_FROM_REPLICA_WITH_FALLBACK )
			? IDBAccessObject::READ_LATEST : 0;

		// TODO: WikiPageEntityMetaDataLookup should use RevisionStore::getQueryInfo,
		// then we could use RevisionStore::newRevisionFromRow here!
		$revision = $this->revisionStore->getRevisionById( $row->rev_id, $revStoreFlags );
		if ( $revision === null ) {
			return [ null, null ];
		}

		$slotRole = $row->role_name ?? SlotRecord::MAIN;

		return $this->entityDataLoader->loadEntityDataFromWikiPageRevision( $revision, $slotRole, $revStoreFlags );
	}

}

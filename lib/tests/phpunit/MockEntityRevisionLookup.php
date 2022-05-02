<?php
declare( strict_types=1 );

namespace Wikibase\Lib\Tests;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;

/**
 * @license GPL-2.0-or-later
 */
class MockEntityRevisionLookup implements EntityRevisionLookup {
	private $redirects;
	private $entities;

	/**
	 * @param [ Serialised EntityId ][ int $revisionId ][ RedirectEntityRevision $revision ] $redirects
	 * @param [ Serialised EntityId ][ int $revisionId ][ EntityRevision $revision ] $entities
	 */
	public function __construct( array $redirects, array $entities ) {
		$this->redirects = $redirects;
		$this->entities = $entities;
	}

	/**
	 * @see EntityRevisionLookup::getEntityRevision
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
		$key = $entityId->getSerialization();

		if ( isset( $this->redirects[$key] ) ) {
			$redirRev = $this->redirects[$key];
			throw new RevisionedUnresolvedRedirectException(
				$entityId,
				$redirRev->getRedirect()->getTargetId(),
				$redirRev->getRevisionId(),
				$redirRev->getTimestamp()
			);
		}

		if ( empty( $this->entities[$key] ) ) {
			return null;
		}

		if ( !is_int( $revisionId ) ) {
			wfWarn( 'getEntityRevision() called with $revisionId = false or a string, use 0 instead.' );
			$revisionId = 0;
		}

		/** @var EntityRevision[] $revisions */
		$revisions = $this->entities[$key];

		if ( $revisionId === 0 ) {
			$revisionIds = array_keys( $revisions );
			$revisionId = end( $revisionIds );
		} elseif ( !isset( $revisions[$revisionId] ) ) {
			throw new StorageException( "no such revision for entity $key: $revisionId" );
		}

		$revision = $revisions[$revisionId];
		$revision = new EntityRevision( // return a copy!
			$revision->getEntity()->copy(), // return a copy!
			$revision->getRevisionId(),
			$revision->getTimestamp()
		);

		return $revision;
	}

	/**
	 * @see EntityRevisionLookup::getLatestRevisionId
	 *
	 * @param EntityId $entityId
	 * @param string $mode
	 *
	 * @return LatestRevisionIdResult
	 */
	public function getLatestRevisionId( EntityId $entityId, $mode = LookupConstants::LATEST_FROM_REPLICA ) {
		try {
			$revision = $this->getEntityRevision( $entityId, 0, $mode );
		} catch ( RevisionedUnresolvedRedirectException $e ) {
			return LatestRevisionIdResult::redirect( $e->getRevisionId(), $e->getRedirectTargetId() );
		}

		return $revision === null
			? LatestRevisionIdResult::nonexistentEntity()
			: LatestRevisionIdResult::concreteRevision( $revision->getRevisionId(), $revision->getTimestamp() );
	}

}

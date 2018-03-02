<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Assert\RepositoryNameAssert;
use Wikibase\DataModel\Entity\EntityId;
use Wikimedia\Assert\Assert;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * Delegates lookup to the repository-specific EntityRevisionLookup
 * based on the name of the repository an EntityId belongs to.
 *
 * @license GPL-2.0-or-later
 */
class DispatchingEntityRevisionLookup implements EntityRevisionLookup {

	/**
	 * @var EntityRevisionLookup[]
	 */
	private $lookups;

	/**
	 * @param EntityRevisionLookup[] $lookups associative array with repository names (strings) as keys
	 *                                        and EntityRevisionLookup objects as values. Empty-string
	 *                                        key defines a lookup for the local repository.
	 *
	 * @throws ParameterAssertionException
	 */
	public function __construct( array $lookups ) {
		Assert::parameter(
			!empty( $lookups ),
			'$lookups',
			'must not be empty'
		);
		Assert::parameterElementType( EntityRevisionLookup::class, $lookups, '$lookups' );
		RepositoryNameAssert::assertParameterKeysAreValidRepositoryNames( $lookups, '$lookups' );
		$this->lookups = $lookups;
	}

	/**
	 * @see EntityRevisionLookup::getEntityRevision
	 * Returns null also when $entityId does not belong to the repository with the configured lookup.
	 *
	 * @param EntityId $entityId
	 * @param int $revisionId The desired revision id, or 0 for the latest revision.
	 * @param string $mode LATEST_FROM_REPLICA, LATEST_FROM_REPLICA_WITH_FALLBACK or LATEST_FROM_MASTER
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
		$lookup = $this->getLookupForEntityId( $entityId );
		return $lookup !== null ? $lookup->getEntityRevision( $entityId, $revisionId, $mode ) : null;
	}

	/**
	 * @see EntityRevisionLookup::getLatestRevisionId
	 * Returns false also when $entityId does not belong to the repository with the configured lookup.
	 *
	 * @param EntityId $entityId
	 * @param string $mode LATEST_FROM_REPLICA, LATEST_FROM_REPLICA_WITH_FALLBACK or LATEST_FROM_MASTER
	 *
	 * @return int|false
	 */
	public function getLatestRevisionId( EntityId $entityId, $mode = self::LATEST_FROM_REPLICA ) {
		$lookup = $this->getLookupForEntityId( $entityId );
		return $lookup !== null ? $lookup->getLatestRevisionId( $entityId, $mode ) : false;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return EntityRevisionLookup|null
	 */
	private function getLookupForEntityId( EntityId $entityId ) {
		$repo = $entityId->getRepositoryName();
		return isset( $this->lookups[$repo] ) ? $this->lookups[$repo] : null;
	}

}

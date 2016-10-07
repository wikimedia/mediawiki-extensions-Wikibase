<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityRevision;
use Wikimedia\Assert\Assert;
use Wikimedia\Assert\ParameterAssertionException;
use Wikimedia\Assert\ParameterElementTypeException;

/**
 * Wrapper around repository-specific EntityRevisionLookups
 * picking up the right lookup object for the particular input.
 *
 * @license GPL-2.0+
 */
class MultiplexingEntityRevisionLookup implements EntityRevisionLookup {

	private $localLookup;

	private $foreignLookups;

	/**
	 * @param EntityRevisionLookup $localLookup
	 * @param EntityRevisionLookup[] $foreignLookups associative array with repository names (strings) as keys
	 *                                               and EntityRevisionLookup objects as values
	 *
	 * @throws ParameterAssertionException
	 * @throws ParameterElementTypeException
	 */
	public function __construct( EntityRevisionLookup $localLookup, array $foreignLookups = [] ) {
		Assert::parameterElementType( EntityRevisionLookup::class, $foreignLookups, '$foreignLookups' );
		Assert::parameterElementType( 'string', array_keys( $foreignLookups ), 'array_keys( $foreignLookups )' );
		foreach ( array_keys( $foreignLookups ) as $repositoryName ) {
			Assert::parameter(
				$repositoryName !== '' && strpos( $repositoryName, ':' ) === false,
				'array_keys( $foreignLookups )',
				'must not contain empty string and string including colons'
			);
		}
		$this->localLookup = $localLookup;
		$this->foreignLookups = $foreignLookups;
	}

	/**
	 * @see   EntityRevisionLookup::getEntityRevision
	 *
	 * @param EntityId $entityId
	 * @param int $revisionId The desired revision id, or 0 for the latest revision.
	 * @param string $mode LATEST_FROM_SLAVE, LATEST_FROM_SLAVE_WITH_FALLBACK or
	 *        LATEST_FROM_MASTER.
	 *
	 * @throws RevisionedUnresolvedRedirectException
	 * @throws StorageException
	 * @throws InvalidArgumentException
	 * @return EntityRevision|null
	 */
	public function getEntityRevision(
		EntityId $entityId,
		$revisionId = 0,
		$mode = self::LATEST_FROM_SLAVE
	) {
		$lookup = $this->getLookupForEntityId( $entityId );
		return $lookup->getEntityRevision( $entityId, $revisionId, $mode );
	}

	/**
	 * @see EntityRevisionLookup::getLatestRevisionId
	 *
	 * @param EntityId $entityId
	 * @param string $mode
	 *
	 * @return int|false
	 * @throws InvalidArgumentException
	 */
	public function getLatestRevisionId( EntityId $entityId, $mode = self::LATEST_FROM_SLAVE ) {
		$lookup = $this->getLookupForEntityId( $entityId );
		return $lookup->getLatestRevisionId( $entityId, $mode );
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return EntityRevisionLookup
	 * @throws InvalidArgumentException
	 */
	private function getLookupForEntityId( EntityId $entityId ) {
		if ( !$entityId->isForeign() ) {
			return $this->localLookup;
		}

		$repo = $entityId->getRepositoryName();
		if ( !isset( $this->foreignLookups[$repo] ) ) {
			throw new InvalidArgumentException( 'Unknown repository: ' . $repo );
		}
		return $this->foreignLookups[$repo];
	}

}

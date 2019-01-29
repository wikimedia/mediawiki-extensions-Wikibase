<?php

namespace Wikibase\DataAccess;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikimedia\Assert\Assert;

/**
 * An EntityRevisionLookup that dispatches by entity type to inner EntityRevisionLookups.
 * If no lookup is registered for the entity type the the lookup will fail in an un exceptional
 * way.
 *
 * TODO Wikibase\Lib\Store\TypeDispatchingEntityRevisionLookup is similar, but a bit different,
 * also conceptually. The other class could maybe be renamed or so?
 *
 * TODO this has been introduced into data-access with a couple of points that still bind to
 * wikibase lib and other parts of mediawiki, these should be cleaned up:
 *  - Wikibase\Lib\Store\EntityRevisionLookup;
 *   - Wikibase\Lib\Store\RevisionedUnresolvedRedirectException
 *   - Wikibase\Lib\Store\StorageException
 *    - MWException
 *    - Status
 *  - Wikibase\Lib\Store\LatestRevisionIdResult;
 *
 * @license GPL-2.0-or-later
 */
class ByTypeDispatchingEntityRevisionLookup implements EntityRevisionLookup {

	/**
	 * EntityRevisionLookup[]
	 */
	private $lookups;

	public function __construct( array $lookups ) {
		Assert::parameterElementType( EntityRevisionLookup::class, $lookups, '$lookups' );
		Assert::parameterElementType( 'string', array_keys( $lookups ), 'keys of $lookups' );

		$this->lookups = $lookups;
	}

	public function getEntityRevision(
		EntityId $entityId,
		$revisionId = 0,
		$mode = self::LATEST_FROM_REPLICA
	) {
		$lookup = $this->getLookupForEntity( $entityId );

		if ( $lookup === null ) {
			return null;
		}

		return $lookup->getEntityRevision( $entityId, $revisionId, $mode );
	}

	public function getLatestRevisionId( EntityId $entityId, $mode = self::LATEST_FROM_REPLICA ) {
		$lookup = $this->getLookupForEntity( $entityId );

		if ( $lookup === null ) {
			return LatestRevisionIdResult::nonexistentEntity();
		}

		return $lookup->getLatestRevisionId( $entityId, $mode );
	}

	/**
	 * @param EntityId $entityId
	 * @return EntityRevisionLookup|null
	 */
	private function getLookupForEntity( EntityId $entityId ) {
		$entityType = $entityId->getEntityType();
		if ( !array_key_exists( $entityType, $this->lookups ) ) {
			return null;
		}

		return $this->lookups[$entityType];
	}

}

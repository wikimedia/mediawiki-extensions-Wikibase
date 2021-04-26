<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityRedirectTargetLookup;

/**
 * @license GPL-2.0-or-later
 */
class RevisionBasedEntityRedirectTargetLookup implements EntityRedirectTargetLookup {

	private $entityRevisionLookup;

	public function __construct( EntityRevisionLookup $entityRevisionLookup ) {
		$this->entityRevisionLookup = $entityRevisionLookup;
	}

	/**
	 * @inheritDoc
	 */
	public function getRedirectForEntityId( EntityId $entityId, $forUpdate = '' ): ?EntityId {
		$returnNull = function () {
			return null;
		};

		$lookupMode = $forUpdate === EntityRedirectTargetLookup::FOR_UPDATE
			? LookupConstants::LATEST_FROM_MASTER
			: LookupConstants::LATEST_FROM_REPLICA;

		return $this->entityRevisionLookup->getLatestRevisionId( $entityId, $lookupMode )
			->onNonexistentEntity( $returnNull )
			->onConcreteRevision( $returnNull )
			->onRedirect( function ( $revision, EntityId $redirectTarget ) {
				return $redirectTarget;
			} )
			->map();
	}

}

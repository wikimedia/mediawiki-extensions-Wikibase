<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
class RedirectResolvingLatestRevisionLookup {

	/**
	 * @var EntityRevisionLookup
	 */
	private $revisionLookup;

	/** @var array results of previously resolved entities keyed by entity id */
	private $resolvedEntities = [];

	public function __construct( EntityRevisionLookup $revisionLookup ) {
		$this->revisionLookup = $revisionLookup;
	}

	/**
	 * @param EntityId $entityId
	 * @return array|null Returns a tuple containing revision ID and target entity ID.
	 *                    If the entity is not present or there is a double redirect null
	 *                    is returned.
	 * @phan-return array{0:int,1:EntityId}
	 */
	public function lookupLatestRevisionResolvingRedirect( EntityId $entityId ) {
		if ( isset( $this->resolvedEntities[$entityId->getSerialization()] ) ) {
			return $this->resolvedEntities[$entityId->getSerialization()];
		}

		$revisionIdResult = $this->revisionLookup->getLatestRevisionId( $entityId );
		$returnNull = function () {
			return null;
		};

		$resolutionResult = $revisionIdResult
			->onConcreteRevision( function ( $revisionId ) use ( $entityId ) {
				return [ $revisionId, $entityId ];
			} )
			->onNonexistentEntity( $returnNull )
			->onRedirect( function ( $revisionId, EntityId $redirectsTo ) use ( $returnNull ) {
				return $this->revisionLookup->getLatestRevisionId( $redirectsTo )
					->onNonexistentEntity( $returnNull )
					->onRedirect( $returnNull )
					->onConcreteRevision( function ( $revisionId ) use ( $redirectsTo ) {
						return [ $revisionId, $redirectsTo ];
					} )
					->map();
			} )
			->map();

		$this->resolvedEntities[$entityId->getSerialization()] = $resolutionResult;

		return $resolutionResult;
	}

}

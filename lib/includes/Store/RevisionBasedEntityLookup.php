<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;

/**
 * An implementation of EntityLookup based on an EntityRevisionLookup.
 *
 * This implementation does not resolve redirects.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class RevisionBasedEntityLookup implements EntityLookup {

	/**
	 * @var EntityRevisionLookup
	 */
	private $lookup;

	/**
	 * @var string
	 */
	private $lookupMode;

	public function __construct( EntityRevisionLookup $lookup, string $lookupMode = LookupConstants::LATEST_FROM_REPLICA ) {
		$this->lookup = $lookup;
		$this->lookupMode = $lookupMode;
	}

	/**
	 * @see EntityLookup::getEntity
	 *
	 * @param EntityId $entityId
	 *
	 * @throws EntityLookupException
	 * @return EntityDocument|null
	 */
	public function getEntity( EntityId $entityId ) {
		try {
			$revision = $this->lookup->getEntityRevision( $entityId,  0, $this->lookupMode );
		} catch ( EntityLookupException $ex ) {
			throw $ex;
		} catch ( \Exception $ex ) {
			// TODO: catch more specific exception once EntityRevisionLookup contract gets clarified
			throw new EntityLookupException( $entityId, $ex->getMessage(), $ex );
		}

		return $revision ? $revision->getEntity() : null;
	}

	/**
	 * @see EntityLookup::hasEntity
	 *
	 * @param EntityId $entityId
	 *
	 * @throws EntityLookupException
	 * @return bool
	 */
	public function hasEntity( EntityId $entityId ) {
		$returnFalse = function () {
			return false;
		};
		$returnTrue = function () {
			return true;
		};

		try {
			$revisionIdResult = $this->lookup->getLatestRevisionId( $entityId );

			return $revisionIdResult
				->onConcreteRevision( $returnTrue )
				->onNonexistentEntity( $returnFalse )
				// @phan-suppress-next-line PhanPluginNeverReturnFunction
				->onRedirect( function ( $revisionId, EntityId $redirectsTo ) use ( $entityId ) {

					throw new UnresolvedEntityRedirectException( $entityId, $redirectsTo );
				} )
				->map();
		} catch ( EntityLookupException $ex ) {
			throw $ex;
		} catch ( \Exception $ex ) {
			// TODO: catch more specific exception once EntityRevisionLookup contract gets clarified
			throw new EntityLookupException( $entityId, $ex->getMessage(), $ex );
		}
	}

}

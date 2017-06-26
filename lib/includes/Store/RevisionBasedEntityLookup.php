<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;

/**
 * An implementation of EntityLookup based on an EntityRevisionLookup.
 *
 * This implementation does not resolve redirects.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class RevisionBasedEntityLookup implements EntityLookup {

	/**
	 * @var EntityRevisionLookup
	 */
	private $lookup;

	public function __construct( EntityRevisionLookup $lookup ) {
		$this->lookup = $lookup;
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
			$revision = $this->lookup->getEntityRevision( $entityId );
		} catch ( EntityLookupException $ex ) {
			throw $ex;
		} catch ( \Exception $ex ) {
			// TODO: catch more specific exception once EntityRevisionLookup contract gets clarified
			throw new EntityLookupException( $entityId, $ex->getMessage(), $ex );
		}

		return $revision === null ? null : $revision->getEntity();
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
		try {
			return $this->lookup->getLatestRevisionId( $entityId ) !== false;
		} catch ( EntityLookupException $ex ) {
			throw $ex;
		} catch ( \Exception $ex ) {
			// TODO: catch more specific exception once EntityRevisionLookup contract gets clarified
			throw new EntityLookupException( $entityId, $ex->getMessage(), $ex );
		}
	}

}

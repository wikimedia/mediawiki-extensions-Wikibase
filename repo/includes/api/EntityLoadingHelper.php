<?php

namespace Wikibase\Repo\Api;

use LogicException;
use UsageException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\BadRevisionException;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;

/**
 * Helper class for api modules to load entities.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class EntityLoadingHelper {

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	public function __construct(
		EntityRevisionLookup $entityRevisionLookup,
		ApiErrorReporter $errorReporter
	) {
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->errorReporter = $errorReporter;
	}

	/**
	 * Load the entity content of the given revision.
	 *
	 * Will fail by calling dieException() $this->errorReporter if the revision
	 * cannot be found or cannot be loaded.
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId EntityId of the page to load the revision for
	 * @param int|string $revId revision to load. If not given, the current revision will be loaded.
	 *
	 * @throws UsageException
	 * @throws LogicException
	 *
	 * @return EntityRevision
	 */
	public function loadEntityRevision(
		EntityId $entityId,
		$revId = EntityRevisionLookup::LATEST_FROM_MASTER
	) {
		try {
			$revision = $this->entityRevisionLookup->getEntityRevision( $entityId, $revId );

			if ( !$revision ) {
				$this->errorReporter->dieError(
					'Entity ' . $entityId->getSerialization() . ' not found',
					'cant-load-entity-content' );
			}

			return $revision;
		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			$this->errorReporter->dieException( $ex, 'unresolved-redirect' );
		} catch ( BadRevisionException $ex ) {
			$this->errorReporter->dieException( $ex, 'nosuchrevid' );
		} catch ( StorageException $ex ) {
			$this->errorReporter->dieException( $ex, 'cant-load-entity-content' );
		}

		throw new LogicException( 'ApiErrorReporter::dieException did not throw a UsageException' );
	}

}

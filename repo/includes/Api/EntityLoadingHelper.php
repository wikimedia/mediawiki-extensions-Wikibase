<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use LogicException;
use UsageException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\BadRevisionException;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikimedia\Assert\Assert;

/**
 * Helper class for api modules to load entities.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class EntityLoadingHelper {

	/**
	 * @var EntityRevisionLookup
	 */
	protected $entityRevisionLookup;

	/**
	 * @var ApiErrorReporter
	 */
	protected $errorReporter;

	/**
	 * @var string See the LATEST_XXX constants defined in EntityRevisionLookup
	 */
	protected $defaultRetrievalMode = EntityRevisionLookup::LATEST_FROM_SLAVE;

	public function __construct(
		EntityRevisionLookup $entityRevisionLookup,
		ApiErrorReporter $errorReporter
	) {
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->errorReporter = $errorReporter;
	}

	/**
	 * @return string
	 */
	public function getDefaultRetrievalMode() {
		return $this->defaultRetrievalMode;
	}

	/**
	 * @param string $defaultRetrievalMode Use the LATEST_XXX constants defined
	 *        in EntityRevisionLookup
	 */
	public function setDefaultRetrievalMode( $defaultRetrievalMode ) {
		Assert::parameterType( 'string', $defaultRetrievalMode, '$defaultRetrievalMode' );
		$this->defaultRetrievalMode = $defaultRetrievalMode;
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
	 * @param int|string|null $revId revision to load, or the retrieval mode,
	 *        see the LATEST_XXX constants defined in EntityRevisionLookup.
	 *        If not given, the current revision will be loaded, using the default retrieval mode.
	 *
	 * @throws UsageException
	 * @throws LogicException
	 * @return EntityRevision
	 */
	protected function loadEntityRevision(
		EntityId $entityId,
		$revId = null
	) {
		if ( $revId === null ) {
			$revId = $this->defaultRetrievalMode;
		}

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

	/**
	 * @param EntityId $entityId
	 * @return EntityDocument
	 */
	public function loadEntity( EntityId $entityId ) {
		$entityRevision = $this->loadEntityRevision( $entityId );
		return $entityRevision->getEntity();
	}

}

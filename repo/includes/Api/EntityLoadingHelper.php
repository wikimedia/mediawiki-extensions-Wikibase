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
	 * @param int|string $revId revision to load. If not given, the current revision will be loaded.
	 *
	 * @throws UsageException
	 * @throws LogicException
	 * @return EntityRevision|null
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
	 * Returns the given EntityDocument.
	 *
	 * @param EntityId|null $entityId ID of the entity to load. If not given, the ID is taken
	 *        from the request parameters. If $entityId is coven, it must be consistent with
	 *        the 'baserevid' parameter.
	 * @return EntityDocument
	 */
	public function loadEntity( EntityId $entityId = null ) {
		$params = $this->apiBase->extractRequestParams();

		if ( !$entityId ) {
			$entityId = $this->getEntityIdFromParams( $params );
		}

		$entityRevision = $this->loadEntityRevision( $entityId );

		if ( !$entityRevision ) {
			$this->errorReporter->dieError(
				'Entity ' . $entityId->getSerialization() . ' not found',
				'cant-load-entity-content' );
		}

		return $entityRevision->getEntity();
	}

	/**
	 * @param string[] $params
	 *
	 * @return EntityId|null
	 */
	protected function getEntityIdFromParams( array $params ) {
		if ( isset( $params['id'] ) ) {
			return $this->getEntityIdFromString( $params['id'] );
		} elseif ( isset( $params['site'] ) && isset( $params['title'] ) ) {
			return $this->getEntityIdFromSiteTitleCombination(
				$params['site'],
				$params['title']
			);
		}

		return null;
	}

	/**
	 * Returns an EntityId object based on the given $id,
	 * or throws a usage exception if the ID is invalid.
	 *
	 * @param string $id
	 *
	 * @throws UsageException
	 * @return EntityId
	 */
	private function getEntityIdFromString( $id ) {
		try {
			return $this->idParser->parse( $id );
		} catch ( EntityIdParsingException $ex ) {
			$this->errorReporter->dieException( $ex, 'no-such-entity-id' );
		}

		return null;
	}

	/**
	 * @param string $site
	 * @param string $title
	 *
	 * @throws UsageException If no such entity is found.
	 * @return EntityId The ID of the entity connected to $title on $site.
	 */
	private function getEntityIdFromSiteTitleCombination( $site, $title ) {
		// FIXME: Normalization missing, see T47282.
		$itemId = $this->siteLinkLookup->getItemIdForLink( $site, $title );

		if ( $itemId === null ) {
			$this->errorReporter->dieError( 'No entity found matching site link ' . $site . ':' . $title,
			                                'no-such-entity-link' );
		}

		return $itemId;
	}

}

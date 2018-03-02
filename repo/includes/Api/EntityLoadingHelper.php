<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use LogicException;
use ApiUsageException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\BadRevisionException;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityByLinkedTitleLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikimedia\Assert\Assert;

/**
 * Helper class for api modules to load entities.
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Daniel Kinzler
 */
class EntityLoadingHelper {

	/**
	 * @var ApiBase
	 */
	protected $apiModule;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

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
	protected $defaultRetrievalMode = EntityRevisionLookup::LATEST_FROM_REPLICA;

	/**
	 * @var EntityByLinkedTitleLookup|null
	 */
	private $entityByLinkedTitleLookup = null;

	/**
	 * @var string
	 */
	private $entityIdParam = 'entity';

	public function __construct(
		ApiBase $apiModule,
		EntityIdParser $idParser,
		EntityRevisionLookup $entityRevisionLookup,
		ApiErrorReporter $errorReporter
	) {
		$this->apiModule = $apiModule;
		$this->idParser = $idParser;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->errorReporter = $errorReporter;
	}

	/**
	 * Returns the name of the request parameter expected to contain the ID of the entity to load.
	 *
	 * @return string
	 */
	public function getEntityIdParam() {
		return $this->entityIdParam;
	}

	/**
	 * Sets the name of the request parameter expected to contain the ID of the entity to load.
	 *
	 * @param string $entityIdParam
	 */
	public function setEntityIdParam( $entityIdParam ) {
		$this->entityIdParam = $entityIdParam;
	}

	public function setEntityByLinkedTitleLookup( EntityByLinkedTitleLookup $lookup ) {
		$this->entityByLinkedTitleLookup = $lookup;
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
	 * @param EntityId $entityId EntityId of the page to load the revision for
	 * @param int $revId The desired revision id, or 0 for the latest revision.
	 * @param string|null $mode LATEST_FROM_REPLICA, LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *        LATEST_FROM_MASTER (from EntityRevisionLookup). Null for the default.
	 *
	 * @throws ApiUsageException
	 * @throws LogicException
	 * @return EntityRevision|null
	 */
	protected function loadEntityRevision(
		EntityId $entityId,
		$revId = 0,
		$mode = null
	) {
		if ( $revId === null ) {
			$revId = 0;
		}
		if ( $mode === null ) {
			$mode = $this->defaultRetrievalMode;
		}

		try {
			$revision = $this->entityRevisionLookup->getEntityRevision( $entityId, $revId, $mode );
			return $revision;
		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			$this->errorReporter->dieException( $ex, 'unresolved-redirect' );
		} catch ( BadRevisionException $ex ) {
			$this->errorReporter->dieException( $ex, 'nosuchrevid' );
		} catch ( StorageException $ex ) {
			$this->errorReporter->dieException( $ex, 'cant-load-entity-content' );
		}

		throw new LogicException( 'ApiErrorReporter::dieException did not throw an ApiUsageException' );
	}

	/**
	 * @param EntityId|null $entityId ID of the entity to load. If not given, the ID is taken
	 *        from the request parameters. If $entityId is given, it must be consistent with
	 *        the 'baserevid' parameter.
	 *
	 * @return EntityDocument
	 */
	public function loadEntity( EntityId $entityId = null ) {
		if ( !$entityId ) {
			$params = $this->apiModule->extractRequestParams();
			$entityId = $this->getEntityIdFromParams( $params );
		}

		if ( !$entityId ) {
			$this->errorReporter->dieError(
				'No entity ID provided',
				'no-entity-id' );
		}

		$entityRevision = $this->loadEntityRevision( $entityId );

		if ( !$entityRevision ) {
			$this->errorReporter->dieWithError( [ 'no-such-entity', $entityId ],
				'no-such-entity' );
		}

		return $entityRevision->getEntity();
	}

	/**
	 * @param string[] $params
	 *
	 * @return EntityId|null
	 */
	protected function getEntityIdFromParams( array $params ) {
		if ( isset( $params[$this->entityIdParam] ) ) {
			return $this->getEntityIdFromString( $params[$this->entityIdParam] );
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
	 * @throws ApiUsageException
	 * @return EntityId
	 */
	private function getEntityIdFromString( $id ) {
		try {
			return $this->idParser->parse( $id );
		} catch ( EntityIdParsingException $ex ) {
			$this->errorReporter->dieException( $ex, 'invalid-entity-id' );
		}

		return null;
	}

	/**
	 * @param string $site
	 * @param string $title
	 *
	 * @throws ApiUsageException If no such entity is found.
	 * @return EntityId The ID of the entity connected to $title on $site.
	 */
	private function getEntityIdFromSiteTitleCombination( $site, $title ) {
		if ( $this->entityByLinkedTitleLookup ) {
			// FIXME: Normalization missing, see T47282. Use EntityByTitleHelper!
			$entityId = $this->entityByLinkedTitleLookup->getEntityIdForLinkedTitle( $site, $title );
		} else {
			$entityId = null;
		}

		if ( $entityId === null ) {
			$this->errorReporter->dieError(
				'No entity found matching site link ' . $site . ':' . $title,
				'no-such-entity-link'
			);
		}

		return $entityId;
	}

}

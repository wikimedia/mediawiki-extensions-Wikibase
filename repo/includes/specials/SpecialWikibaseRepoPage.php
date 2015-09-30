<?php

namespace Wikibase\Repo\Specials;

use MWException;
use RuntimeException;
use SiteStore;
use Status;
use Title;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EditEntityFactory;
use Wikibase\EntityRevision;
use Wikibase\Lib\MessageException;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\UserInputException;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * Abstract base class for special pages of the WikibaseRepo extension.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
abstract class SpecialWikibaseRepoPage extends SpecialWikibasePage {

	/**
	 * @var SummaryFormatter
	 */
	protected $summaryFormatter;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var SiteStore
	 */
	protected $siteStore;

	/**
	 * @var EditEntityFactory
	 */
	private $editEntityFactory;

	/**
	 * @since 0.5
	 *
	 * @param string $title The title of the special page
	 * @param string $restriction The required user right
	 */
	public function __construct( $title, $restriction ) {
		parent::__construct( $title, $restriction );
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$this->setSpecialWikibaseRepoPageServices(
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->getSiteStore(),
			$wikibaseRepo->newEditEntityFactory( $this->getContext() )
		);
	}

	/**
	 * Override services (for testing).
	 *
	 * @param SummaryFormatter $summaryFormatter
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param SiteStore $siteStore
	 * @param EditEntityFactory $editEntityFactory
	 */
	public function setSpecialWikibaseRepoPageServices(
		SummaryFormatter $summaryFormatter,
		EntityRevisionLookup $entityRevisionLookup,
		EntityTitleLookup $entityTitleLookup,
		SiteStore $siteStore,
		EditEntityFactory $editEntityFactory
	) {
		$this->summaryFormatter = $summaryFormatter;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->siteStore = $siteStore;
		$this->editEntityFactory = $editEntityFactory;
	}

	/**
	 * Parses an entity id.
	 *
	 * @param string $rawId
	 *
	 * @return EntityId
	 *
	 * @throws UserInputException
	 */
	protected function parseEntityId( $rawId ) {
		$idParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();

		try {
			$id = $idParser->parse( $rawId );
		} catch ( RuntimeException $ex ) {
			throw new UserInputException(
				'wikibase-wikibaserepopage-invalid-id',
				array( $rawId ),
				'Entity id is not valid'
			);
		}

		return $id;
	}

	/**
	 * Parses an item id.
	 *
	 * @param string $rawId
	 *
	 * @return ItemId
	 *
	 * @throws UserInputException
	 */
	protected function parseItemId( $rawId ) {
		/** @var EntityId $id */
		$id = $this->parseEntityId( $rawId );
		if ( !( $id instanceof ItemId ) ) {
			throw new UserInputException(
				'wikibase-wikibaserepopage-not-itemid',
				array( $rawId ),
				'Entity id does not belong to an item'
			);
		}
		return $id;
	}

	/**
	 * Loads the entity for this entity id.
	 *
	 * @since 0.5
	 *
	 * @param EntityId $id
	 * @param int|string $revisionId
	 *
	 * @throws MessageException
	 * @throws UserInputException
	 * @return EntityRevision
	 */
	protected function loadEntity( EntityId $id, $revisionId = EntityRevisionLookup::LATEST_FROM_MASTER ) {
		try {
			$entity = $this->entityRevisionLookup->getEntityRevision( $id, $revisionId );

			if ( $entity === null ) {
				throw new UserInputException(
					'wikibase-wikibaserepopage-invalid-id',
					array( $id->getSerialization() ),
					'Entity id is unknown'
				);
			}
		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			throw new UserInputException(
				'wikibase-wikibaserepopage-unresolved-redirect',
				array( $id->getSerialization() ),
				'Entity id refers to a redirect'
			);
		} catch ( StorageException $ex ) {
			throw new MessageException(
				'wikibase-wikibaserepopage-storage-exception',
				array( $id->getSerialization(), $ex->getMessage() ),
				'Entity could not be loaded'
			);
		}

		return $entity;
	}

	/**
	 * @since 0.5
	 *
	 * @param EntityId $id
	 *
	 * @throws MWException
	 * @return null|Title
	 */
	protected function getEntityTitle( EntityId $id ) {
		return $this->entityTitleLookup->getTitleForId( $id );
	}

	/**
	 * Saves the entity using the given summary.
	 *
	 * @param Entity $entity
	 * @param Summary $summary
	 * @param string $token
	 * @param int $flags The edit flags (see WikiPage::doEditContent)
	 * @param bool|int $baseRev the base revision, for conflict detection
	 *
	 * @return Status
	 */
	protected function saveEntity(
		Entity $entity,
		Summary $summary,
		$token,
		$flags = EDIT_UPDATE,
		$baseRev = false
	) {
		$editEntity = $this->editEntityFactory->newEditEntity(
			$this->getUser(),
			$entity,
			$baseRev
		);

		$status = $editEntity->attemptSave(
			$this->summaryFormatter->formatSummary( $summary ),
			$flags,
			$token
		);

		return $status;
	}

}

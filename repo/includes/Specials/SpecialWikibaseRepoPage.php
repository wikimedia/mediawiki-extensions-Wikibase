<?php

namespace Wikibase\Repo\Specials;

use MWException;
use RuntimeException;
use SiteStore;
use Status;
use Title;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EditEntityFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\UserInputException;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * Abstract base class for special pages of the WikibaseRepo extension.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
abstract class SpecialWikibaseRepoPage extends SpecialWikibasePage {

	/**
	 * @var SummaryFormatter
	 */
	protected $summaryFormatter;

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
	 * @var EntityIdParser
	 */
	private $entityIdParser;

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
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->getSiteStore(),
			$wikibaseRepo->newEditEntityFactory( $this->getContext() ),
			$wikibaseRepo->getEntityIdParser()
		);
	}

	/**
	 * Override services (for testing).
	 *
	 * @param SummaryFormatter $summaryFormatter
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param SiteStore $siteStore
	 * @param EditEntityFactory $editEntityFactory
	 */
	public function setSpecialWikibaseRepoPageServices(
		SummaryFormatter $summaryFormatter,
		EntityTitleLookup $entityTitleLookup,
		SiteStore $siteStore,
		EditEntityFactory $editEntityFactory,
		EntityIdParser $entityIdParser
	) {
		$this->summaryFormatter = $summaryFormatter;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->siteStore = $siteStore;
		$this->editEntityFactory = $editEntityFactory;
		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * Parses an entity id.
	 *
	 * @param string $rawId
	 *
	 * @return EntityId
	 * @throws UserInputException
	 */
	protected function parseEntityId( $rawId ) {
		try {
			$id = $this->entityIdParser->parse( $rawId );
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
	 * @param EntityDocument $entity
	 * @param Summary $summary
	 * @param string $token
	 * @param int $flags The edit flags (see WikiPage::doEditContent)
	 * @param bool|int $baseRev the base revision, for conflict detection
	 *
	 * @return Status
	 */
	protected function saveEntity(
		EntityDocument $entity,
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

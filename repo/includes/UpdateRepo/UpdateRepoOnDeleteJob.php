<?php

namespace Wikibase\Repo\UpdateRepo;

use OutOfBoundsException;
use SiteLookup;
use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\EditEntityFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * Job for updating the repo after a page on the client has been deleted.
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnDeleteJob extends UpdateRepoJob {

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * Constructs a UpdateRepoOnMoveJob propagating a page move to the repo
	 *
	 * @note: This is for use by Job::factory, don't call it directly;
	 *           use newFrom*() instead.
	 *
	 * @note: the constructor's signature is dictated by Job::factory, so we'll have to
	 *           live with it even though it's rather ugly for our use case.
	 *
	 * @see Job::factory
	 * @see UpdateRepoJob::__construct
	 *
	 * @param Title $title
	 * @param array|bool $params
	 */
	public function __construct( Title $title, $params = false ) {
		parent::__construct( 'UpdateRepoOnDelete', $title, $params );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$this->initServices(
			$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
			$wikibaseRepo->getEntityStore(),
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->getSiteLookup(),
			$wikibaseRepo->newEditEntityFactory()
		);
	}

	public function initServices(
		EntityRevisionLookup $entityRevisionLookup,
		EntityStore $entityStore,
		SummaryFormatter $summaryFormatter,
		SiteLookup $siteLookup,
		EditEntityFactory $editEntityFactory
	) {
		$this->initRepoJobServices(
			$entityRevisionLookup,
			$entityStore,
			$summaryFormatter,
			$editEntityFactory
		);
		$this->siteLookup = $siteLookup;
	}

	/**
	 * Get a SiteLink for a specific item and site
	 *
	 * @param Item $item
	 * @param string $globalId
	 *
	 * @return SiteLink|null
	 */
	private function getSiteLink( Item $item, $globalId ) {
		try {
			return $item->getSiteLinkList()->getBySiteId( $globalId );
		} catch ( OutOfBoundsException $e ) {
			return null;
		}
	}

	/**
	 * Get a Summary object for the edit
	 *
	 * @return Summary
	 */
	public function getSummary() {
		$params = $this->getParams();
		$siteId = $params['siteId'];
		$page = $params['title'];

		return new Summary(
			'clientsitelink',
			'remove',
			null,
			[ $siteId ],
			[ $page ]
		);
	}

	/**
	 * Whether the propagated update is valid (and thus should be applied)
	 *
	 * @param Item $item
	 *
	 * @return bool
	 */
	protected function verifyValid( Item $item ) {
		$params = $this->getParams();
		$siteId = $params['siteId'];
		$page = $params['title'];

		$siteLink = $this->getSiteLink( $item, $siteId );
		if ( !$siteLink || $siteLink->getPageName() !== $page ) {
			// Probably something changed since the job has been inserted
			wfDebugLog( 'UpdateRepo', "OnDelete: The site link to " . $siteId . " is no longer $page" );
			return false;
		}

		$site = $this->siteLookup->getSite( $siteId );

		// Maybe the page has been undeleted/ recreated?
		$exists = $site->normalizePageName( $page );
		if ( $exists !== false ) {
			wfDebugLog( 'UpdateRepo', "OnDelete: $page on $siteId exists" );
			return false;
		}

		return true;
	}

	/**
	 * Apply the changes needed to the given Item.
	 *
	 * @param Item $item
	 *
	 * @return bool
	 */
	protected function applyChanges( Item $item ) {
		$params = $this->getParams();
		$siteId = $params['siteId'];

		$item->getSiteLinkList()->removeLinkWithSiteId( $siteId );
	}

}

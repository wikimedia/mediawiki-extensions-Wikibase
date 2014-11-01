<?php

namespace Wikibase\Repo\UpdateRepo;

use Job;
use OutOfBoundsException;
use SiteStore;
use Title;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\WikibaseRepo;

/**
 * Job for updating the repo after a page on the client has been deleted.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnDeleteJob extends UpdateRepoJob {

	/**
	 * @var SiteStore
	 */
	private $siteStore;

	/**
	 * Constructs a UpdateRepoOnMoveJob propagating a page move to the repo
	 *
	 * @note: This is for use by Job::factory, don't call it directly;
	 *           use newFrom*() instead.
	 *
	 * @note: the constructor's signature is dictated by Job::factory, so we'll have to
	 *           live with it even though it's rather ugly for our use case.
	 *
	 * @see Job::factory.
	 *
	 * @param Title $title Ignored
	 * @param array|bool $params
	 */
	public function __construct( Title $title, $params = false ) {
		parent::__construct( 'UpdateRepoOnDelete', $title, $params );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$this->initServices(
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
			$wikibaseRepo->getEntityStore(),
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->getEntityPermissionChecker(),
			$wikibaseRepo->getSiteStore()
		);
	}

	public function initServices(
		EntityTitleLookup $entityTitleLookup,
		EntityRevisionLookup $entityRevisionLookup,
		EntityStore $entityStore,
		SummaryFormatter $summaryFormatter,
		EntityPermissionChecker $entityPermissionChecker,
		SiteStore $siteStore
	) {
		parent::initServices(
			$entityTitleLookup,
			$entityRevisionLookup,
			$entityStore,
			$summaryFormatter,
			$entityPermissionChecker
		);
		$this->siteStore = $siteStore;
	}

	/**
	 * Get a SiteLink for a specific item and site
	 *
	 * @param Item $item
	 * @param string $globalId
	 *
	 * @return SiteLink|null
	 */
	private function getSiteLink( $item, $globalId ) {
		try {
			return $item->getSiteLink( $globalId );
		} catch( OutOfBoundsException $e ) {
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
			array( $siteId ),
			array( $page )
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
		wfProfileIn( __METHOD__ );
		$params = $this->getParams();
		$siteId = $params['siteId'];
		$page = $params['title'];

		$item = $this->getItem();

		$siteLink = $this->getSiteLink( $item, $siteId );
		if ( !$siteLink || $siteLink->getPageName() !== $page ) {
			// Probably something changed since the job has been inserted
			wfDebugLog( 'UpdateRepo', "OnDelete: The site link to " . $siteId . " is no longer $page" );
			wfProfileOut( __METHOD__ );
			return false;
		}

		$site = $this->siteStore->getSite( $siteId );

		// Maybe the page has been undeleted/ recreated?
		$exists = $site->normalizePageName( $page );
		if ( $exists !== false ) {
			wfDebugLog( 'UpdateRepo', "OnDelete: $page on $siteId exists" );
			wfProfileOut( __METHOD__ );
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

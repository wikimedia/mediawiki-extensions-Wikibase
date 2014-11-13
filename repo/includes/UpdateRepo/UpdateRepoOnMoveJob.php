<?php

namespace Wikibase\Repo\UpdateRepo;

use OutOfBoundsException;
use SiteStore;
use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * Job for updating the repo after a page on the client has been moved.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnMoveJob extends UpdateRepoJob {

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
		parent::__construct( 'UpdateRepoOnMove', $title, $params );

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
		$this->initRepoJobServices(
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
			return $item->getSiteLinkList()->getBySiteId( $globalId );
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
		$oldPage = $params['oldTitle'];
		$newPage = $params['newTitle'];

		return new Summary(
			'clientsitelink',
			'update',
			$siteId,
			array(
				$siteId . ":$oldPage",
				$siteId . ":$newPage",
			)
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
		$oldPage = $params['oldTitle'];
		$newPage = $params['newTitle'];

		$oldSiteLink = $this->getSiteLink( $item, $siteId );
		if ( !$oldSiteLink || $oldSiteLink->getPageName() !== $oldPage ) {
			// Probably something changed since the job has been inserted
			wfDebugLog( 'UpdateRepo', "OnMove: The site link to " . $siteId . " is no longer $oldPage" );
			wfProfileOut( __METHOD__ );
			return false;
		}

		$site = $this->siteStore->getSite( $siteId );
		// Normalize the name again, just in case the page has been updated in the mean time
		if ( !$site || !$site->normalizePageName( $newPage ) ) {
			wfDebugLog( 'UpdateRepo', "OnMove: Normalizing the page name $newPage on $siteId failed" );
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
		$newPage = $params['newTitle'];

		$oldSiteLink = $this->getSiteLink( $item, $siteId );

		$site = $this->siteStore->getSite( $siteId );
		$siteLink = new SiteLink(
			$siteId,
			$site->normalizePageName( $newPage ),
			$oldSiteLink->getBadges() // Keep badges
		);

		$item->getSiteLinkList()->removeLinkWithSiteId( $siteId );
		$item->getSiteLinkList()->addSiteLink( $siteLink );
	}

}

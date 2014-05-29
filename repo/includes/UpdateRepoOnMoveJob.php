<?php

namespace Wikibase;

use Job;
use OutOfBoundsException;
use Site;
use Title;
use User;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Lib\Store\EntityStore;

/**
 * Job for updating the repo after a page on the client has been moved.
 *
 * This needs to be in lib as the client needs it for injecting it and
 * the repo to execute it.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnMoveJob extends Job {

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
	 * @param integer $id
	 */
	public function __construct( Title $title, $params = false, $id = 0 ) {
		parent::__construct( 'UpdateRepoOnMove', $title, $params, $id );
	}

	/**
	 * @return EntityContentFactory
	 */
	protected function getEntityContentFactory() {
		return WikibaseRepo::getDefaultInstance()->getEntityContentFactory();
	}

	/**
	 * @return EntityTitleLookup
	 */
	protected function getEntityTitleLookup() {
		return WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
	}

	/**
	 * @return EntityRevisionLookup
	 */
	protected function getEntityRevisionLookup() {
		return WikibaseRepo::getDefaultInstance()->getEntityRevisionLookup( 'uncached' );
	}

	/**
	 * @return EntityStore
	 */
	protected function getEntityStore() {
		return WikibaseRepo::getDefaultInstance()->getEntityStore();
	}

	/**
	 * @return SummaryFormatter
	 */
	protected function getSummaryFormatter() {
		return WikibaseRepo::getDefaultInstance()->getSummaryFormatter();
	}

	/**
	 * @return EntityPermissionChecker
	 */
	protected function getEntityPermissionChecker() {
		return WikibaseRepo::getDefaultInstance()->getEntityPermissionChecker();
	}

	/**
	 * Get a Site object for a global id
	 *
	 * @param string $globalId
	 *
	 * @return Site
	 */
	protected function getSite( $globalId ) {
		$sitesStore =  WikibaseRepo::getDefaultInstance()->getSiteStore();
		return $sitesStore->getSite( $globalId );
	}

	/**
	 * Get a SimpleSiteLink for a specific item and site
	 *
	 * @param Item $item
	 * @param string $globalId
	 *
	 * @return SimpleSiteLink|null
	 */
	protected function getSimpleSiteLink( $item, $globalId ) {
		try {
			return $item->getSiteLink( $globalId );
		} catch( OutOfBoundsException $e ) {
			return null;
		}
	}

	/**
	 * Get a Summary object for the edit
	 *
	 * @param string $globalId Global id of the target site
	 * @param string $oldPage
	 * @param string $newPage
	 *
	 * @return Summary
	 */
	public function getSummary( $globalId, $oldPage, $newPage ) {
		return new Summary(
			'clientsitelink',
			'update',
			$globalId,
			array(
				$globalId . ":$oldPage",
				$globalId . ":$newPage",
			)
		);
	}

	/**
	 * Update the siteLink on the repo to reflect the change in the client
	 *
	 * @param string $siteId Id of the client the change comes from
	 * @param string $itemId
	 * @param string $oldPage
	 * @param string $newPage
	 * @param User $user User who we'll attribute the update to
	 *
	 * @return bool Whether something changed
	 */
	public function updateSiteLink( $siteId, $itemId, $oldPage, $newPage, $user ) {
		wfProfileIn( __METHOD__ );

		$itemId = new ItemId( $itemId );
		$item = $this->getEntityRevisionLookup()->getEntity( $itemId );
		if ( !$item ) {
			// The entity assigned with the moved page can't be found
			wfDebugLog( __CLASS__, __FUNCTION__ . ": entity with id " . $itemId->getPrefixedId() . " not found" );
			wfProfileOut( __METHOD__ );
			return false;
		}

		$site = $this->getSite( $siteId );

		$oldSiteLink = $this->getSimpleSiteLink( $item, $siteId );
		if ( !$oldSiteLink || $oldSiteLink->getPageName() !== $oldPage ) {
			// Probably something changed since the job has been inserted
			wfDebugLog( __CLASS__, __FUNCTION__ . ": The site link to " . $siteId . " is no longer $oldPage" );
			wfProfileOut( __METHOD__ );
			return false;
		}

		// Normalize the name again, just in case the page has been updated in the mean time
		$newPage = $site->normalizePageName( $newPage );
		if ( !$newPage ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": Normalizing the page name $newPage failed" );
			wfProfileOut( __METHOD__ );
			return false;
		}

		$siteLink = new SimpleSiteLink(
			$siteId,
			$newPage
		);

		$summary = $this->getSummary( $siteId, $oldPage, $newPage );

		return $this->doUpdateSiteLink( $item, $siteLink, $summary, $user );
	}

	/**
	 * Update the given item with the given sitelink
	 *
	 * @param Item $item
	 * @param SiteLink $siteLink
	 * @param Summary $summary
	 * @param User $user User who we'll attribute the update to
	 *
	 * @return bool Whether something changed
	 */
	public function doUpdateSiteLink( $item, $siteLink, $summary, $user ) {
		$item->addSiteLink( $siteLink );

		$editEntity = new EditEntity(
			$this->getEntityTitleLookup(),
			$this->getEntityRevisionLookup(),
			$this->getEntityStore(),
			$this->getEntityPermissionChecker(),
			$item,
			$user,
			true
		);

		$summaryString = $this->getSummaryFormatter()->formatSummary( $summary );

		$status = $editEntity->attemptSave(
			$summaryString,
			EDIT_UPDATE,
			false,
			// Don't (un)watch any pages here, as the user didn't explicitly kick this off
			$this->getEntityStore()->isWatching( $user, $item->getid() )
		);

		wfProfileOut( __METHOD__ );

		// TODO: Analyze what happened and let the user know in case a manual fix could be needed
		return $status->isOK();
	}

	/**
	 * Run the job
	 *
	 * @return boolean success
	 */
	public function run() {
		wfProfileIn( __METHOD__ );
		$params = $this->getParams();

		$user = User::newFromName( $params['user'] );
		if ( !$user || !$user->isLoggedIn() ) {
			// This should never happen as we check with CentralAuth
			// that the user actually does exist
			wfLogWarning( 'User ' . $params['user'] . " doesn't exist while CentralAuth pretends it does" );
			wfProfileOut( __METHOD__ );
			return true;
		}

		$this->updateSiteLink(
			$params['siteId'],
			$params['entityId'],
			$params['oldTitle'],
			$params['newTitle'],
			$user
		);

		wfProfileOut( __METHOD__ );
		return true;
	}

}

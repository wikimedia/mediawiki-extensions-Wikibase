<?php

namespace Wikibase;

/**
 * Job for updating the repo after a page on the client has been moved.
 *
 * This needs to be in lib as the client needs it for injecting it and
 * the repo to execute it.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnMoveJob extends \Job {

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
	 * @param \Title $title Ignored
	 * @param array|bool $params
	 * @param integer $id
	 */
	public function __construct( \Title $title, $params = false, $id = 0 ) {
		parent::__construct( 'UpdateRepoOnMove', $title, $params, $id );
	}

	/**
	 * Get the item content for the item we're about to update
	 *
	 * @param string $siteId Id of the client the change comes from
	 * @param string $oldPage
	 *
	 * @return ItemContent|null
	 */
	public function getItemContent( $siteId, $oldPage ) {
		$itemHandler = new ItemHandler();
		$itemContent = $itemHandler->getFromSiteLink( $siteId, $oldPage );

		return $itemContent;
	}

	/**
	 * Update the siteLink on the repo to reflect the change in the client
	 *
	 * @param string $siteId Id of the client the change comes from
	 * @param string $oldPage
	 * @param string $newPage
	 * @param \User $user User who we'll attribute the update to
	 *
	 * @return bool Whether something changed
	 */
	public function updateSiteLink( $siteId, $oldPage, $newPage, $user ) {
		wfProfileIn( __METHOD__ );

		$itemContent = $this->getItemContent( $siteId, $oldPage );
		if ( !$itemContent ) {
			// The moved page doesn't have an item assigned or the user already
			// did the change, so there's nothing left to do
			wfProfileOut( __METHOD__ );
			return false;
		}

		$editEntity = new EditEntity( $itemContent, $user, true );

		$item = $itemContent->getItem();

		$sitesTable = \SiteSQLStore::newInstance();
		$site = $sitesTable->getSite( $siteId );
		// Normalize the name again, just in case the page has been updated in the mean time
		$newPage = $site->normalizePageName( $newPage );
		if ( !$newPage ) {
			wfProfileOut( __METHOD__ );
			return false;
		}

		$siteLink = new SiteLink(
			$site,
			$newPage
		);

		if ( !$item->addSiteLink( $siteLink, 'update' ) ) {
			// Probably something changed since the job has been inserted
			wfProfileOut( __METHOD__ );
			return false;
		}

		$summary = new Summary(
			'clientsitelink',
			'set',
			$siteLink->getSite()->getGlobalId(),
			array(),
			array( $siteLink->getPage() )
		);

		$status = $editEntity->attemptSave(
			$summary->toString(),
			0,
			false,
			false
		);

		wfProfileOut( __METHOD__ );

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

		$user = \User::newFromName( $params['user'] );
		if ( !$user || !$user->isLoggedIn() ) {
			// This should never happen as we check with CentralAuth
			// that the user actually does exist
			trigger_error( "User $user doesn't exist while CentralAuth pretends it does", E_USER_WARNING );
			wfProfileOut( __METHOD__ );
			return true;
		}

		$this->updateSiteLink( $params['siteID'], $params['oldTitle'], $params['newTitle'], $user );

		wfProfileOut( __METHOD__ );
		return true;
	}
}

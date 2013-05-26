<?php

namespace Wikibase;

/**
 * Job for updating the repo after a page on the client has been moved.
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
	 * Creates a UpdateRepoOnMoveJob representing the given move.
	 *
	 * @param \Title $oldTitle Old title
	 * @param \Title $newTitle New Title
	 * @param \User $user User who moved the page
	 * @param string $globalId Global id of the site from which the is coming
	 * @param array|bool $params extra job parameters, see Job::__construct (default: false).
	 *
	 * @return \Wikibase\UpdateRepoOnMoveJob: the job
	 */
	public static function newFromMove( $oldTitle, $newTitle, $user, $globalId, $params = false ) {
		wfProfileIn( __METHOD__ );

		$site = \MediaWikiSite::newFromGlobalId( $globalId );

		if ( $params === false ) {
			$params = array();
		}

		$params['siteID'] = $globalId;
		$params['oldTitle'] = $oldTitle->getPrefixedDBkey();
		$params['newTitle'] = $newTitle->getPrefixedDBkey();
		$params['user'] = $user->getName();

		// The Title object isn't really being used but \Job demands it
		$job = new UpdateRepoOnMoveJob( $newTitle, $params );

		wfProfileOut( __METHOD__ );
		return $job;
	}

	/**
	 * Constructs a UpdateRepoOnMoveJob propagating a page move to the repo
	 *
	 * @note: This is for use by Job::factory, don't call it directly;
	 *           use newFrom*() instead.
	 *
	 * @note: the constructor's signature is dictated by Job::factory, so we'll have to
	 *           live with it even though it's rather ugly for our use case.
	 *
	 * @see      Job::factory.
	 *
	 * @param \Title $title ignored
	 * @param  $params array|bool
	 * @param  $id     int
	 */
	public function __construct( \Title $title, $params = false, $id = 0 ) {
		parent::__construct( 'UpdateRepoOnMove', $title, $params, $id );
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
			return;
		}

		$itemHandler = new ItemHandler();
		$itemContent = $itemHandler->getFromSiteLink( $params['siteID'], $params['oldTitle'] );
		if ( !$itemContent ) {
			// The moved page doesn't have an item assigned or the user already
			// did the change, so there's nothing left to do
			wfProfileOut( __METHOD__ );
			return true;
		}

		$editEntity = new EditEntity( $itemContent, $user, true );

		$item = $itemContent->getItem();
		$siteLinks = $item->getSiteLinks();

		$site = \MediaWikiSite::newFromGlobalId( $params['siteID'] );
		// Normalize the name again, just in case the page has been updated in the mean time
		$params['newTitle'] = $site->normalizePageName( $params['newTitle'] );

		$siteLink = new SiteLink(
			$site,
			$params['newTitle']
		);

		$siteLinks[ $params['siteID'] ] = $siteLink;
		if ( !$item->addSiteLink( $siteLink, 'update' ) ) {
			// Probably something changed since the job has bin inserted
			wfProfileOut( __METHOD__ );
			return true;
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
		return true;
	}
}

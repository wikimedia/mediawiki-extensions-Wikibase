<?php

namespace Wikibase;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\SimpleSiteLink;

/**
 * Provides logic to update the repo after certain changes have been
 * performed in the client (like page moves).
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
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
abstract class UpdateRepo {

	/**
	 * @var string
	 */
	protected $repoDB;

	/**
	 * @var \User
	 */
	protected $user;

	/**
	 * @var \SiteLinkLookup
	 */
	protected $siteLinkLookup;

	/**
	 * @var string
	 */
	protected $siteId;

	/**
	 * @var \Title
	 */
	protected $title;

	/**
	 * @param string $repoDB Database name of the repo
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param \User $user
	 * @param string $siteId Global id of the client wiki
	 * @param \Title $title Title in the client that has been changed
	 */
	public function __construct( $repoDB, $siteLinkLookup, $user, $siteId, $title ) {
		$this->repoDB = $repoDB;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->user = $user;
		$this->siteId = $siteId;
		$this->title = $title;
	}

	/**
	 * Get the EntityId that we want to update
	 *
	 * @return EntityId|null
	 */
	public function getEntityId() {
		return $this->siteLinkLookup->getEntityIdForSiteLink(
			new SimpleSiteLink(
				$this->siteId,
				$this->title->getFullText()
			)
		);
	}

	/**
	 * Find out whether the user also exists on the repo and belongs to the
	 * same global account (uses CentralAuth).
	 *
	 * @return bool
	 */
	public function userIsValidOnRepo() {
		if ( !class_exists( 'CentralAuthUser' ) ) {
			// We can't do anything without CentralAuth as there's no way to verify that
			// the local user equals the repo one with the same name
			return false;
		}

		$caUser = \CentralAuthUser::getInstance( $this->user );
		if ( !$caUser || !$caUser->exists() ) {
			// The current user doesn't have a central account
			return false;
		}

		// XXX: repoDatabase == CentralAuth site id?!!
		if ( !$caUser->isAttached() || !$caUser->attachedOn( $this->repoDB ) ) {
			// Either the user account on this wiki or the one on the repo do not exist
			// or they aren't connected
			return false;
		}

		return true;
	}

	/**
	 * Inject the current job into the job queue of the repo
	 *
	 * @throws \RuntimeException
	 *
	 * @param \JobQueueGroup $jobQueueGroup
	 */
	public function injectJob( \JobQueueGroup $jobQueueGroup ) {
		wfProfileIn( __METHOD__ );

		$job = $this->createJob();

		wfProfileIn( __METHOD__ . '#push' );
		$ok = $jobQueueGroup->push( $job );
		wfProfileOut( __METHOD__ . '#push' );

		if ( !$ok ) {
			wfProfileOut( __METHOD__ );
			throw new \RuntimeException( "Failed to push job to job queue" );
		}

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Returns a new job for updating the repo.
	 *
	 * @return \Job
	 */
	abstract public function createJob();
}

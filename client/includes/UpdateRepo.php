<?php

namespace Wikibase;

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
	 * @var \Job
	 */
	protected $job;

	/**
	 * @var \User
	 */
	protected $user;

	/**
	 * @var string
	 */
	protected $siteId;

	/**
	 * @param string $repoDB Database name of the repo
	 * @param \User $user
	 * @param string $siteId Global id of the client wiki
	 */
	public function __construct( $repoDB, $user, $siteId ) {
		$this->repoDB = $repoDB;
		$this->user = $user;
		$this->siteId = $siteId;
	}

	/**
	 * Find out whether the user also exists on the repo and belongs to the
	 * same global account (uses CentralAuth per default).
	 *
	 * @return bool
	 */
	public function verifyRepoUser() {
		if ( !class_exists( 'CentralAuthUser' ) ) {
			// We can't do anything without CentralAuth as there's no way to verify that
			// the local user equals the repo one of the same name
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
	 * @return bool
	 */
	public function injectJob() {
		wfProfileIn( __METHOD__ );

		$this->getJob();

		if ( !$this->job ) {
			throw new \MWException( 'Tried to inject invalid job' );
		}

		wfProfileIn( __METHOD__ . '#queue' );
		$jobQueueGroup = \JobQueueGroup::singleton( $this->repoDB );
		wfProfileOut( __METHOD__ . '#queue' );

		if ( !$jobQueueGroup ) {
			trigger_error( "Failed to acquire a JobQueueGroup for $this->repoDB", E_USER_WARNING );
			wfProfileOut( __METHOD__ );
			return false;
		}

		wfProfileIn( __METHOD__ . '#push' );
		$ok = $jobQueueGroup->push( $this->job );
		wfProfileOut( __METHOD__ . '#push' );

		if ( !$ok ) {
			trigger_error( "Failed to push to job queue for $this->repoDB", E_USER_WARNING );
			wfProfileOut( __METHOD__ );
			return false;
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Propagates $this->job with a new job for updating the repo.
	 */
	abstract public function getJob();
}

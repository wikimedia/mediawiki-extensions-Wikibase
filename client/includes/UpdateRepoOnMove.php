<?php

namespace Wikibase;

/**
 * Provides logic to update the repo after page moves in the client.
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
class UpdateRepoOnMove extends UpdateRepo {
	/**
	 * @var \Title
	 */
	protected $oldTitle;

	/**
	 * @var \Title
	 */
	protected $newTitle;

	/**
	 * @param string $repoDB Database name of the repo
	 * @param \User $user
	 * @param string $siteId Global id of the client wiki
	 * @param \Title $oldTitle
	 * @param \Title $newTitle
	 */
	public function __construct( $repoDB, $user, $siteId, $oldTitle, $newTitle ) {
		parent::__construct( $repoDB, $user, $siteId );
		$this->newTitle = $newTitle;
		$this->oldTitle = $oldTitle;
	}

	/**
	 * Propagates $this->job with a new job for updating the repo.
	 *
	 * @return \Job
	 */
	public function getJob() {
		wfProfileIn( __METHOD__ );

		if ( $this->job ) {
			wfProfileOut( __METHOD__ );
			return $this->job;
		}

		$params = array(
			'siteID' => $this->siteId,
			'oldTitle' => $this->oldTitle->getPrefixedDBkey(),
			'newTitle' => $this->newTitle->getPrefixedDBkey(),
			'user' => $this->user->getName()
		);

		// The Title object isn't really being used but \Job demands it
		$this->job = new UpdateRepoOnMoveJob( $this->newTitle, $params );

		wfProfileOut( __METHOD__ );

		return $this->job;
	}

	/**
	 * Create a new instance from a move action.
	 *
	 * @param \Title $oldTitle
	 * @param \Title $newTitle
	 * @param \User $user User who moved the page
	 *
	 * @return \Wikibase\UpdateRepoOnMove|null
	 */
	public static function newFromMove( $oldTitle, $newTitle, $user ) {
		wfProfileIn( __METHOD__ );
		$repoDB = Settings::get( 'repoDatabase' );
		$globalId = Settings::get( 'siteGlobalID' );

		$updateRepo = new self(
			$repoDB,
			$user,
			$globalId,
			$oldTitle,
			$newTitle
		);

		wfProfileOut( __METHOD__ );
		return $updateRepo;
	}
}

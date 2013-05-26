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
	protected $newTitle;

	/**
	 * @param string $repoDB Database name of the repo
	 * @param \JobQueueGroup $jobQueueGroup JobQueueGroup to insert jobs into
	 * @param \User $user
	 * @param string $siteId Global id of the client wiki
	 * @param \Title $oldTitle
	 * @param \Title $newTitle
	 */
	public function __construct( $repoDB, $jobQueueGroup, $user, $siteId, $oldTitle, $newTitle ) {
		parent::__construct( $repoDB, $jobQueueGroup, $user, $siteId, $oldTitle );
		$this->newTitle = $newTitle;
	}

	/**
	 * Returns a new job for updating the repo.
	 *
	 * @return \Job
	 */
	public function createJob() {
		wfProfileIn( __METHOD__ );

		$job = UpdateRepoOnMoveJob::newFromMove(
			$this->title,
			$this->newTitle,
			$this->getEntityId(),
			$this->user,
			$this->siteId
		);

		wfProfileOut( __METHOD__ );

		return $job;
	}
}

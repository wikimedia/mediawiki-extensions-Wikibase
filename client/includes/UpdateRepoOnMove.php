<?php

namespace Wikibase;

/**
 * Provides logic to update the repo after page moves in the client.
 *
 * @since 0.4
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
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param \User $user
	 * @param string $siteId Global id of the client wiki
	 * @param \Title $oldTitle
	 * @param \Title $newTitle
	 */
	public function __construct( $repoDB, $siteLinkLookup, $user, $siteId, $oldTitle, $newTitle ) {
		parent::__construct( $repoDB, $siteLinkLookup, $user, $siteId, $oldTitle );
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

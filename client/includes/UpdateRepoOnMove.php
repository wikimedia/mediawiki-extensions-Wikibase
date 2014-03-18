<?php

namespace Wikibase;
use Wikibase\DataModel\Entity\ItemId;
use IJobSpecification;
use JobSpecification;
use Title;
use User;

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
	 * @var Title
	 */
	protected $newTitle;

	/**
	 * @param string $repoDB Database name of the repo
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param User $user
	 * @param string $siteId Global id of the client wiki
	 * @param Title $oldTitle
	 * @param Title $newTitle
	 */
	public function __construct(
		$repoDB,
		SiteLinkLookup $siteLinkLookup,
		User $user, $siteId,
		Title $oldTitle,
		Title $newTitle
	) {
		parent::__construct( $repoDB, $siteLinkLookup, $user, $siteId, $oldTitle );
		$this->newTitle = $newTitle;
	}

	/**
	 * Get the parameters for creating a new JobSpecification
	 *
	 * @param Title $oldTitle
	 * @param Title $newTitle
	 * @param ItemId $itemId
	 * @param User $user User who moved the page
	 * @param string $globalId Global id of the site from which the is coming
	 *
	 * @return JobSpecification
	 */
	protected function getJobParameters(
		Title $oldTitle,
		Title $newTitle,
		ItemId $itemId,
		User $user,
		$globalId
	) {
		return array(
			'siteId' => $globalId,
			'entityId' => $itemId->getSerialization(),
			'oldTitle' => $oldTitle->getPrefixedText(),
			'newTitle' => $newTitle->getPrefixedText(),
			'user' => $user->getName()
		);
	}

	/**
	 * Returns a new job for updating the repo.
	 *
	 * @return IJobSpecification
	 */
	public function createJob() {
		wfProfileIn( __METHOD__ );

		$params = $this->getJobParameters(
			$this->title,
			$this->newTitle,
			$this->getEntityId(),
			$this->user,
			$this->siteId
		);
		$job = new JobSpecification( 'UpdateRepoOnMove', $params );

		wfProfileOut( __METHOD__ );

		return $job;
	}
}

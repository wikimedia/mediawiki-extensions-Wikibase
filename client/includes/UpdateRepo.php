<?php

namespace Wikibase;

use CentralAuthUser;
use IJobSpecification;
use JobQueueGroup;
use JobSpecification;
use RuntimeException;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * Provides logic to update the repo after certain changes have been
 * performed in the client (like page moves).
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
abstract class UpdateRepo {

	/**
	 * @var string
	 */
	private $repoDB;

	/**
	 * @since 0.4
	 * @var User
	 */
	protected $user;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @since 0.4
	 * @var string
	 */
	protected $siteId;

	/**
	 * @since 0.4
	 * @var Title
	 */
	protected $title;

	/**
	 * @var EntityId|null|bool
	 */
	private $entityId = false;

	/**
	 * @param string $repoDB Database name of the repo
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param User $user
	 * @param string $siteId Global id of the client wiki
	 * @param Title $title Title in the client that has been changed
	 */
	public function __construct(
		$repoDB,
		SiteLinkLookup $siteLinkLookup,
		User $user,
		$siteId,
		Title $title
	) {
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
		if ( $this->entityId === false ) {
			$this->entityId = $this->siteLinkLookup->getEntityIdForSiteLink(
				new SiteLink(
					$this->siteId,
					$this->title->getFullText()
				)
			);
		}

		return $this->entityId;
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

		$caUser = CentralAuthUser::getInstance( $this->user );
		if ( !$caUser || !$caUser->exists() ) {
			// The current user doesn't have a central account
			return false;
		}

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
	 * @throws RuntimeException
	 *
	 * @param JobQueueGroup $jobQueueGroup
	 */
	public function injectJob( JobQueueGroup $jobQueueGroup ) {
		wfProfileIn( __METHOD__ );

		$job = $this->createJob();

		wfProfileIn( __METHOD__ . '#push' );
		$jobQueueGroup->push( $job );
		wfProfileOut( __METHOD__ . '#push' );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Returns a new job for updating the repo.
	 *
	 * @return IJobSpecification
	 */
	public function createJob() {
		wfProfileIn( __METHOD__ );

		$job = new JobSpecification(
			$this->getJobName(),
			$this->getJobParameters()
		);

		wfProfileOut( __METHOD__ );

		return $job;
	}

	/**
	 * Get the parameters for creating a new IJobSpecification
	 *
	 * @return array
	 */
	abstract protected function getJobParameters();

	/**
	 * Get the name of the Job that should be run on the repo
	 *
	 * @return string
	 */
	abstract protected function getJobName();

}

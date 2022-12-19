<?php

namespace Wikibase\Client\UpdateRepo;

use IJobSpecification;
use JobQueueGroup;
use JobSpecification;
use MediaWiki\User\UserIdentity;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Rdbms\ClientDomainDb;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * Provides logic to update the repo after certain changes have been
 * performed in the client (like page moves).
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
abstract class UpdateRepo {

	/**
	 * @var UserIdentity
	 */
	protected $user;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var string
	 */
	protected $siteId;

	/**
	 * @var Title
	 */
	protected $title;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var ClientDomainDb
	 */
	private $clientDb;

	/**
	 * @var EntityId|null|bool
	 */
	private $entityId = false;

	/**
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param LoggerInterface $logger
	 * @param UserIdentity $user
	 * @param string $siteId Global id of the client wiki
	 * @param Title $title Title in the client that has been changed
	 */
	public function __construct(
		SiteLinkLookup $siteLinkLookup,
		LoggerInterface $logger,
		ClientDomainDb $clientDb,
		UserIdentity $user,
		$siteId,
		Title $title
	) {
		$this->siteLinkLookup = $siteLinkLookup;
		$this->logger = $logger;
		$this->user = $user;
		$this->siteId = $siteId;
		$this->title = $title;
		$this->clientDb = $clientDb;
	}

	/**
	 * Get the EntityId that we want to update
	 *
	 * @return EntityId|null
	 */
	protected function getEntityId() {
		if ( $this->entityId === false ) {
			$this->entityId = $this->siteLinkLookup->getItemIdForLink(
				$this->siteId,
				$this->title->getPrefixedText()
			);

			if ( $this->entityId === null ) {
				$this->logger->debug(
					'Could not find an item for "{titleText}"',
					[
						'titleText' => $this->title->getPrefixedText(),
					]
				);
			}
		}

		return $this->entityId;
	}

	/**
	 * Whether the update can be applied to repo.
	 *
	 * @return bool
	 */
	public function isApplicable() {
		return $this->getEntityId() !== null;
	}

	/**
	 * Inject the current job into the job queue of the repo
	 *
	 * @throws RuntimeException
	 *
	 * @param JobQueueGroup $jobQueueGroup
	 */
	public function injectJob( JobQueueGroup $jobQueueGroup ) {
		$job = $this->createJob( $jobQueueGroup );
		$jobQueueGroup->push( $job );
	}

	/**
	 * Returns a new job for updating the repo.
	 *
	 * @param JobQueueGroup $jobQueueGroup
	 *
	 * @return IJobSpecification
	 */
	private function createJob( JobQueueGroup $jobQueueGroup ) {
		$params = $this->getJobParameters();
		if ( $this->delayJobs( $jobQueueGroup ) ) {
			$params['jobReleaseTimestamp'] = time() + $this->getJobDelay();
		}

		return new JobSpecification(
			$this->getJobName(),
			$params
		);
	}

	/**
	 * @param JobQueueGroup $jobQueueGroup
	 *
	 * @return bool
	 */
	private function delayJobs( JobQueueGroup $jobQueueGroup ) {
		return $jobQueueGroup->get( $this->getJobName() )->delayedJobsEnabled();
	}

	/**
	 * Get the time (in seconds) for which the job execution should be delayed
	 * (if delayed jobs are enabled). Defaults to the max replag of any pooled
	 * DB server + 2 seconds.
	 *
	 * @return int
	 */
	protected function getJobDelay() {
		$lagArray = $this->clientDb->replication()->getMaxLag();
		// This should be good enough, especially given that lagged servers get
		// less load by the load balancer, thus it's very unlikely we'll end
		// up on the server with the highest lag.
		// We add +2 here, to make sure we have a minimum delay of a full
		// second (this is being added to time() so +1 actually just means
		// wait until this second is over).
		return $lagArray[1] + 2;
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

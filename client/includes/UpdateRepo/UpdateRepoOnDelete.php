<?php

namespace Wikibase\Client\UpdateRepo;

/**
 * Provides logic to update the repo after page deletes in the client.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnDelete extends UpdateRepo {

	/**
	 * Get the name of the Job that should be run on the repo
	 *
	 * @return string
	 */
	protected function getJobName() {
		return 'UpdateRepoOnDelete';
	}

	/**
	 * Get the parameters for creating a new JobSpecification
	 *
	 * @return array
	 */
	protected function getJobParameters() {
		return array(
			'siteId' => $this->siteId,
			'entityId' => $this->getEntityId()->getSerialization(),
			'title' => $this->title->getPrefixedText(),
			'user' => $this->user->getName()
		);
	}

	/**
	 * Get the time (in seconds) for which the job execution should be delayed
	 * (if delayed jobs are enabled).
	 *
	 * @return int
	 */
	protected function getJobDelay() {
		// Make sure this is not being run in the next 10s, as otherwise the job
		// might run before the client's api is up with what happened (replag)
		return 10;
	}
}

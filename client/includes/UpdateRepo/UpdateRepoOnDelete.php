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
}

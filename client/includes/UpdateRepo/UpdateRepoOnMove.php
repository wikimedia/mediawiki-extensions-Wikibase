<?php

namespace Wikibase\Client\UpdateRepo;

use MediaWiki\User\UserIdentity;
use Psr\Log\LoggerInterface;
use Title;
use Wikibase\Lib\Rdbms\ClientDomainDb;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * Provides logic to update the repo after page moves in the client.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnMove extends UpdateRepo {

	/**
	 * @var Title
	 */
	private $newTitle;

	/**
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param LoggerInterface $logger
	 * @param UserIdentity $user
	 * @param string $siteId Global id of the client wiki
	 * @param Title $oldTitle
	 * @param Title $newTitle
	 */
	public function __construct(
		SiteLinkLookup $siteLinkLookup,
		LoggerInterface $logger,
		ClientDomainDb $clientDomainDb,
		UserIdentity $user,
		$siteId,
		Title $oldTitle,
		Title $newTitle
	) {
		parent::__construct( $siteLinkLookup, $logger, $clientDomainDb, $user, $siteId, $oldTitle );
		$this->newTitle = $newTitle;
	}

	/**
	 * Get the name of the Job that should be run on the repo
	 *
	 * @return string
	 */
	protected function getJobName() {
		return 'UpdateRepoOnMove';
	}

	/**
	 * Get the parameters for creating a new JobSpecification
	 *
	 * @return array
	 */
	protected function getJobParameters() {
		return [
			'siteId' => $this->siteId,
			'entityId' => $this->getEntityId()->getSerialization(),
			'oldTitle' => $this->title->getPrefixedText(),
			'newTitle' => $this->newTitle->getPrefixedText(),
			'user' => $this->user->getName(),
		];
	}

}

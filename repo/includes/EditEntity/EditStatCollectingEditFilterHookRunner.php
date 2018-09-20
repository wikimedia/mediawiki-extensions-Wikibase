<?php

namespace Wikibase\Repo\EditEntity;

use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use Status;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Repo\Hooks\EditFilterHookRunner;

/**
 * EditFilterHookRunning that collects stats for edits.
 * @license GPL-2.0-or-later
 */
class EditStatCollectingEditFilterHookRunner implements EditFilterHookRunner {

	private $hookRunner;
	private $stats;
	private $timingPrefix;

	/**
	 * @param EditFilterHookRunner $hookRunner
	 * @param StatsdDataFactoryInterface $stats
	 * @param string $timingPrefix e.g. wikibase.repo.EditEntity.timing.attemptSave
	 */
	public function __construct(
		EditFilterHookRunner $hookRunner,
		StatsdDataFactoryInterface $stats,
		$timingPrefix
	) {
		$this->hookRunner = $hookRunner;
		$this->stats = $stats;
		$this->timingPrefix = $timingPrefix;
	}

	/**
	 * @param null|EntityDocument|EntityRedirect $new
	 * @param User $user
	 * @param string $summary
	 * @return Status
	 */
	public function run( $new, User $user, $summary ) {
		$attemptSaveFilterStart = microtime( true );
		$hookStatus = $this->hookRunner->run( $new, $user, $summary );
		$attemptSaveFilterEnd = microtime( true );

		$this->stats->timing(
			"{$this->timingPrefix}.{$new->getType()}.EditFilterHookRunner.run",
			( $attemptSaveFilterEnd - $attemptSaveFilterStart ) * 1000
		);

		return $hookStatus;
	}

}

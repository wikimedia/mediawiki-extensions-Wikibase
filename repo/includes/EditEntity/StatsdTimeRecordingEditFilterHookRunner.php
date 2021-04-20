<?php

namespace Wikibase\Repo\EditEntity;

use IContextSource;
use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use Status;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityRedirect;

/**
 * EditFilterHookRunning that collects stats for edits.
 * @license GPL-2.0-or-later
 */
class StatsdTimeRecordingEditFilterHookRunner implements EditFilterHookRunner {

	/** @var EditFilterHookRunner */
	private $hookRunner;
	/** @var StatsdDataFactoryInterface */
	private $stats;
	/** @var string */
	private $timingPrefix;

	/**
	 * @param EditFilterHookRunner $hookRunner
	 * @param StatsdDataFactoryInterface $stats
	 * @param string $timingPrefix Resulting metric will be: $timingPrefix.run.<entitytype>
	 */
	public function __construct(
		EditFilterHookRunner $hookRunner,
		StatsdDataFactoryInterface $stats,
		string $timingPrefix
	) {
		$this->hookRunner = $hookRunner;
		$this->stats = $stats;
		$this->timingPrefix = $timingPrefix;
	}

	/**
	 * @param null|EntityDocument|EntityRedirect $new
	 * @param IContextSource $context
	 * @param string $summary
	 * @return Status
	 */
	public function run( $new, IContextSource $context, $summary ) {
		$attemptSaveFilterStart = microtime( true );
		$hookStatus = $this->hookRunner->run( $new, $context, $summary );
		$attemptSaveFilterEnd = microtime( true );

		$this->stats->timing(
			"{$this->timingPrefix}.run.{$new->getType()}",
			( $attemptSaveFilterEnd - $attemptSaveFilterStart ) * 1000
		);

		return $hookStatus;
	}

}

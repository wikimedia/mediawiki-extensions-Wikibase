<?php

namespace Wikibase\Repo\EditEntity;

use IContextSource;
use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use Status;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Repo\Content\EntityContent;

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
	 * @param null|EntityDocument|EntityRedirect|EntityContent $new
	 * @param IContextSource $context
	 * @param string $summary
	 * @return Status
	 */
	public function run( $new, IContextSource $context, $summary ) {
		$attemptSaveFilterStart = microtime( true );
		$hookStatus = $this->hookRunner->run( $new, $context, $summary );
		$attemptSaveFilterEnd = microtime( true );

		if ( $new !== null ) {
			if ( $new instanceof EntityDocument ) {
				$entityType = $new->getType();
			} elseif ( $new instanceof EntityRedirect ) {
				$entityType = $new->getEntityId()->getEntityType();
			} elseif ( $new instanceof EntityContent ) {
				$entityType = $new->getEntityId()->getEntityType();
			} else {
				$entityType = 'UNKNOWN';
			}
			$this->stats->timing(
				"{$this->timingPrefix}.run.{$entityType}",
				( $attemptSaveFilterEnd - $attemptSaveFilterStart ) * 1000
			);
		}

		return $hookStatus;
	}

}

<?php

namespace Wikibase\Repo\EditEntity;

use MediaWiki\Context\IContextSource;
use MediaWiki\Status\Status;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Repo\Content\EntityContent;
use Wikimedia\Stats\StatsFactory;

/**
 * EditFilterHookRunning that collects stats for edits.
 * @license GPL-2.0-or-later
 */
class StatslibTimeRecordingEditFilterHookRunner implements EditFilterHookRunner {

	/**
	 * @var EditFilterHookRunner
	 */
	private $hookRunner;

	/**
	 * @var StatsFactory
	 */
	private $statsFactory;

	/**
	 * @var string
	 */
	private $statsdTimingPrefix;

	/**
	 * @var string
	 */
	private $statsTimingPrefix;

	/**
	 * @param EditFilterHookRunner $hookRunner
	 * @param StatsFactory $statsFactory
	 * @param string $statsdTimingPrefix Resulting metric will be: $statsdTimingPrefix.saveEntity.<entitytype>
	 * @param string $statsTimingPrefix
	 */
	public function __construct(
		EditFilterHookRunner $hookRunner,
		StatsFactory $statsFactory,
		string $statsdTimingPrefix,
		string $statsTimingPrefix
	) {
		$this->hookRunner = $hookRunner;
		$this->statsFactory = $statsFactory->withComponent( 'WikibaseRepo' );
		$this->statsdTimingPrefix = $statsdTimingPrefix;
		$this->statsTimingPrefix = $statsTimingPrefix;
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

			$this->statsFactory
			->getTiming( "{$this->statsTimingPrefix}_run_duration_seconds" )
			->setLabel( 'type', $entityType )
			->copyToStatsdAt( "{$this->statsdTimingPrefix}.run.{$entityType}" )
			->observe( ( $attemptSaveFilterEnd - $attemptSaveFilterStart ) * 1000 );
		}

		return $hookStatus;
	}

}
